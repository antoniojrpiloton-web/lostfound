from io import BytesIO
import numpy as np
from PIL import Image
from fastapi.testclient import TestClient

from app.config import settings
from app.main import app

client = TestClient(app)
HEADERS = {"X-Service-Token": settings.service_token}


def _to_jpeg(array: np.ndarray) -> bytes:
    buf = BytesIO()
    Image.fromarray(array.astype(np.uint8), mode="RGB").save(buf, format="JPEG", quality=92)
    return buf.getvalue()


def test_health_endpoint() -> None:
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"


def test_rejects_unauthorized_request() -> None:
    response = client.post("/v1/validate", files={"image": ("a.jpg", b"123", "image/jpeg")})
    assert response.status_code == 401


def test_accepts_sharp_well_lit_image() -> None:
    rng = np.random.default_rng(42)
    img = rng.integers(70, 190, size=(480, 640, 3), dtype=np.uint8)
    response = client.post(
        "/v1/validate",
        headers=HEADERS,
        files={"image": ("good.jpg", _to_jpeg(img), "image/jpeg")},
        data={"category_key": "mobile-phones"},
    )
    assert response.status_code == 200
    assert response.json()["verdict"] == "accepted"


def test_rejects_blurry_flat_image() -> None:
    img = np.full((480, 640, 3), 128, dtype=np.uint8)
    response = client.post(
        "/v1/validate",
        headers=HEADERS,
        files={"image": ("blurry.jpg", _to_jpeg(img), "image/jpeg")},
    )
    assert response.status_code == 200
    payload = response.json()
    assert payload["verdict"] == "rejected"
    assert "The uploaded image is too blurry." in payload["messages"]


def test_rejects_dark_image() -> None:
    img = np.full((480, 640, 3), 10, dtype=np.uint8)
    response = client.post(
        "/v1/validate",
        headers=HEADERS,
        files={"image": ("dark.jpg", _to_jpeg(img), "image/jpeg")},
    )
    payload = response.json()
    assert payload["verdict"] == "rejected"
    assert "The image is too dark." in payload["messages"]


def test_rejects_overexposed_image() -> None:
    img = np.full((480, 640, 3), 252, dtype=np.uint8)
    response = client.post(
        "/v1/validate",
        headers=HEADERS,
        files={"image": ("bright.jpg", _to_jpeg(img), "image/jpeg")},
    )
    payload = response.json()
    assert payload["verdict"] == "rejected"
    assert "The image is overexposed." in payload["messages"]


def test_rejects_corrupted_image() -> None:
    response = client.post(
        "/v1/validate",
        headers=HEADERS,
        files={"image": ("corrupt.jpg", b"not-an-image-at-all", "image/jpeg")},
    )
    payload = response.json()
    assert payload["verdict"] == "rejected"
    assert "The image could not be processed." in payload["messages"]
