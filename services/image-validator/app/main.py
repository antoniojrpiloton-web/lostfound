from fastapi import FastAPI, File, Form, Header, HTTPException, UploadFile

from app.config import settings
from app.validator import extract_image_embedding, validate_image_bytes

app = FastAPI(title="Boracay Lost and Found Image Validation Service", version=settings.service_version)

MAX_BYTES = 10 * 1024 * 1024


@app.get("/health")
def health() -> dict[str, object]:
    return {
        "status": "ok",
        "service_version": settings.service_version,
        "clip_enabled": settings.enable_clip,
    }


@app.post("/v1/validate")
async def validate(
    image: UploadFile = File(...),
    category_key: str | None = Form(default=None),
    blur_reject_threshold: float | None = Form(default=None),
    blur_review_threshold: float | None = Form(default=None),
    dark_mean_threshold: float | None = Form(default=None),
    bright_mean_threshold: float | None = Form(default=None),
    x_service_token: str | None = Header(default=None),
) -> dict[str, object]:
    if x_service_token != settings.service_token:
        raise HTTPException(status_code=401, detail="Invalid service token.")

    raw_bytes = await image.read()
    if len(raw_bytes) == 0 or len(raw_bytes) > MAX_BYTES:
        raise HTTPException(status_code=422, detail="Image payload is empty or exceeds maximum allowed size.")

    overrides: dict[str, float] = {}
    if blur_reject_threshold is not None:
        overrides["blur_reject_threshold"] = blur_reject_threshold
    if blur_review_threshold is not None:
        overrides["blur_review_threshold"] = blur_review_threshold
    if dark_mean_threshold is not None:
        overrides["dark_mean_threshold"] = dark_mean_threshold
    if bright_mean_threshold is not None:
        overrides["bright_mean_threshold"] = bright_mean_threshold

    return validate_image_bytes(raw_bytes, category_key, settings, overrides)


@app.post("/v1/embed")
async def embed(
    image: UploadFile = File(...),
    x_service_token: str | None = Header(default=None),
) -> dict[str, object]:
    if x_service_token != settings.service_token:
        raise HTTPException(status_code=401, detail="Invalid service token.")

    raw_bytes = await image.read()
    if len(raw_bytes) == 0 or len(raw_bytes) > MAX_BYTES:
        raise HTTPException(status_code=422, detail="Image payload is empty or exceeds maximum allowed size.")

    return extract_image_embedding(raw_bytes, settings)
