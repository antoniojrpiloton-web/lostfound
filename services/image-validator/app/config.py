from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    service_token: str = "dev-local-token"
    service_version: str = "1.0.0"
    min_dimension: int = 320
    blur_reject_threshold: float = 80.0
    blur_review_threshold: float = 120.0
    dark_mean_threshold: float = 45.0
    dark_pixel_ratio_threshold: float = 0.70
    bright_mean_threshold: float = 220.0
    bright_pixel_ratio_threshold: float = 0.40
    object_reject_threshold: float = 0.35
    object_review_threshold: float = 0.55
    enable_clip: bool = False
    clip_model_name: str = "ViT-B-32"
    clip_pretrained: str = "laion2b_s34b_b79k"

    model_config = {"env_prefix": "BLF_IMG_"}


settings = Settings()
