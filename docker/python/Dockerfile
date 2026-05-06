FROM python:3.10

WORKDIR /app

RUN apt-get update && apt-get install -y \
    cmake \
    build-essential \
    libgl1 \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

COPY face_recognition_service/requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt

COPY face_recognition_service /app/face_recognition_service

CMD ["python", "face_recognition_service/face_recognition_service.py"]
