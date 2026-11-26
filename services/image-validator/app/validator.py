from io import BytesIO
from typing import Any
import cv2
import numpy as np
from PIL import Image, UnidentifiedImageError

from app.config import Settings

ALLOWED_FORMATS = {"JPEG", "PNG", "WEBP"}

_CLIP_CACHE: dict[str, Any] = {}


def _load_clip(settings: Settings) -> tuple[Any, Any, Any] | None:
    if not settings.enable_clip:
        return None

    if "bundle" in _CLIP_CACHE:
        return _CLIP_CACHE["bundle"]

    try:
        import open_clip
        import torch

        model, _, preprocess = open_clip.create_model_and_transforms(
            settings.clip_model_name,
            pretrained=settings.clip_pretrained,
        )
        tokenizer = open_clip.get_tokenizer(settings.clip_model_name)
        model.eval()
        bundle = (model, preprocess, tokenizer, torch)
        _CLIP_CACHE["bundle"] = bundle
        return bundle
    except Exception:
        _CLIP_CACHE["bundle"] = None
        return None


def _run_clip_checks(
    pil_image: Image.Image,
    category_key: str | None,
    settings: Settings,
) -> dict[str, Any]:
    bundle = _load_clip(settings)
    if bundle is None:
        return {
            "supported": False,
            "model_version": "none",
            "object_probability": None,
            "relevance_probability": None,
            "object_status": "skipped",
            "relevance_status": "skipped",
        }

    model, preprocess, tokenizer, torch = bundle

    object_prompts = [
        "a clear photo of a personal belonging or everyday object",
        "a blank wall, empty floor, plain sky, or solid color background",
    ]

    image_tensor = preprocess(pil_image).unsqueeze(0)
    text_tokens = tokenizer(object_prompts)

    with torch.no_grad():
        image_features = model.encode_image(image_tensor)
        text_features = model.encode_text(text_tokens)
        image_features = image_features / image_features.norm(dim=-1, keepdim=True)
        text_features = text_features / text_features.norm(dim=-1, keepdim=True)
        probs = (100.0 * image_features @ text_features.T).softmax(dim=-1)[0]

    object_prob = float(probs[0].item())
    if object_prob < settings.object_reject_threshold:
        object_status = "rejected"
    elif object_prob < settings.object_review_threshold:
        object_status = "review"
    else:
        object_status = "passed"

    relevance_prob = None
    relevance_status = "skipped"
    if category_key:
        label = category_key.replace("-", " ")
        rel_prompts = [
            f"a photo of {label}",
            "a photo of something completely unrelated",
        ]
        rel_tokens = tokenizer(rel_prompts)
        with torch.no_grad():
            rel_features = model.encode_text(rel_tokens)
            rel_features = rel_features / rel_features.norm(dim=-1, keepdim=True)
            rel_probs = (100.0 * image_features @ rel_features.T).softmax(dim=-1)[0]
        relevance_prob = float(rel_probs[0].item())
        relevance_status = "review" if relevance_prob < 0.15 else "passed"

    return {
        "supported": True,
        "model_version": f"{settings.clip_model_name}:{settings.clip_pretrained}",
        "object_probability": round(object_prob, 4),
        "relevance_probability": round(relevance_prob, 4) if relevance_prob is not None else None,
        "object_status": object_status,
        "relevance_status": relevance_status,
    }


def validate_image_bytes(
    raw_bytes: bytes,
    category_key: str | None,
    settings: Settings,
    overrides: dict[str, float] | None = None,
) -> dict[str, Any]:
    overrides = overrides or {}
    blur_reject = float(overrides.get("blur_reject_threshold", settings.blur_reject_threshold))
    blur_review = float(overrides.get("blur_review_threshold", settings.blur_review_threshold))
    dark_mean = float(overrides.get("dark_mean_threshold", settings.dark_mean_threshold))
    bright_mean = float(overrides.get("bright_mean_threshold", settings.bright_mean_threshold))

    try:
        with Image.open(BytesIO(raw_bytes)) as probe:
            img_format = (probe.format or "").upper()
            probe.verify()
    except (UnidentifiedImageError, OSError, ValueError):
        return {
            "verdict": "rejected",
            "messages": ["The image could not be processed."],
            "checks": {"corrupted": True},
            "service_version": settings.service_version,
            "model_version": "none",
        }

    if img_format not in ALLOWED_FORMATS:
        return {
            "verdict": "rejected",
            "messages": ["Unsupported image format. Use JPG, PNG, or WEBP."],
            "checks": {"format": img_format, "supported_format": False},
            "service_version": settings.service_version,
            "model_version": "none",
        }

    try:
        pil_image = Image.open(BytesIO(raw_bytes)).convert("RGB")
    except (UnidentifiedImageError, OSError, ValueError):
        return {
            "verdict": "rejected",
            "messages": ["The image could not be processed."],
            "checks": {"corrupted": True},
            "service_version": settings.service_version,
            "model_version": "none",
        }

    width, height = pil_image.size
    if min(width, height) < settings.min_dimension:
        return {
            "verdict": "rejected",
            "messages": [f"The image resolution is too small (minimum {settings.min_dimension}px on the shortest side)."],
            "checks": {"width": width, "height": height, "min_dimension_passed": False},
            "service_version": settings.service_version,
            "model_version": "none",
        }

    rgb = np.array(pil_image)
    gray = cv2.cvtColor(rgb, cv2.COLOR_RGB2GRAY)

    long_side = max(width, height)
    if long_side > 1024:
        scale = 1024.0 / float(long_side)
        gray_scaled = cv2.resize(gray, (int(width * scale), int(height * scale)), interpolation=cv2.INTER_AREA)
    else:
        gray_scaled = gray

    laplacian_var = float(cv2.Laplacian(gray_scaled, cv2.CV_64F).var())
    mean_luminance = float(gray.mean())
    dark_ratio = float((gray < 30).mean())
    bright_ratio = float((gray >= 250).mean())

    messages: list[str] = []
    rejected = False
    needs_review = False

    if laplacian_var < blur_reject:
        rejected = True
        messages.append("The uploaded image is too blurry.")
    elif laplacian_var < blur_review:
        needs_review = True
        messages.append("Image sharpness is borderline and requires manual review.")

    if mean_luminance < dark_mean or dark_ratio > settings.dark_pixel_ratio_threshold:
        rejected = True
        messages.append("The image is too dark.")

    if mean_luminance > bright_mean or bright_ratio > settings.bright_pixel_ratio_threshold:
        rejected = True
        messages.append("The image is overexposed.")

    clip_checks = _run_clip_checks(pil_image, category_key, settings)
    if clip_checks["object_status"] == "rejected":
        rejected = True
        messages.append("No recognizable item was detected.")
    elif clip_checks["object_status"] == "review" or clip_checks["relevance_status"] == "review":
        needs_review = True
        messages.append("Image content requires manual administrative review.")

    verdict = "rejected" if rejected else ("review" if needs_review else "accepted")

    return {
        "verdict": verdict,
        "messages": messages,
        "checks": {
            "format": img_format,
            "width": width,
            "height": height,
            "laplacian_variance": round(laplacian_var, 2),
            "mean_luminance": round(mean_luminance, 2),
            "dark_pixel_ratio": round(dark_ratio, 4),
            "bright_pixel_ratio": round(bright_ratio, 4),
            "object_detection": clip_checks,
        },
        "service_version": settings.service_version,
        "model_version": clip_checks["model_version"],
    }


def extract_image_embedding(raw_bytes: bytes, settings: Settings) -> dict[str, Any]:
    bundle = _load_clip(settings)
    if bundle is None:
        return {
            "supported": False,
            "model_version": "none",
            "dimensions": 0,
            "vector": [],
        }

    try:
        pil_image = Image.open(BytesIO(raw_bytes)).convert("RGB")
    except (UnidentifiedImageError, OSError, ValueError):
        return {
            "supported": False,
            "model_version": "none",
            "dimensions": 0,
            "vector": [],
            "error": "The image could not be processed.",
        }

    model, preprocess, _, torch = bundle
    image_tensor = preprocess(pil_image).unsqueeze(0)

    with torch.no_grad():
        features = model.encode_image(image_tensor)
        features = features / features.norm(dim=-1, keepdim=True)
        vec = [round(float(v), 6) for v in features[0].cpu().tolist()]

    return {
        "supported": True,
        "model_version": f"{settings.clip_model_name}:{settings.clip_pretrained}",
        "dimensions": len(vec),
        "vector": vec,
    }
