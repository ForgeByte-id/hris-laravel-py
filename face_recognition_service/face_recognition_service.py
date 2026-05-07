from flask import Flask, request, jsonify
from flask_cors import CORS
import face_recognition
import numpy as np
import os
import json
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)
CORS(app)

DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'mysql'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'database': os.getenv('DB_DATABASE', 'hris_db'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', 'root')
}

ALLOWED_IMAGE_DIRS = [
    os.path.realpath(os.getenv('ALLOWED_IMAGE_TMP_DIR', '/tmp')),
    os.path.realpath(os.getenv('LARAVEL_STORAGE_PATH', '/var/www/html/storage/app')),
]


def get_db_connection():
    try:
        return mysql.connector.connect(**DB_CONFIG)
    except Error as e:
        print(f"DB Error: {e}")
        return None


def load_known_faces():
    conn = get_db_connection()
    if not conn:
        return [], []

    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            "SELECT id_karyawan, face_embedding FROM karyawan WHERE face_embedding IS NOT NULL"
        )
        rows = cursor.fetchall()

        encodings = []
        ids = []

        for row in rows:
            try:
                encodings.append(np.array(json.loads(row['face_embedding'])))
                ids.append(row['id_karyawan'])
            except Exception:
                continue

        print(f"Loaded {len(ids)} faces")
        return encodings, ids

    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()


def get_safe_image_path(image_path):
    if not image_path:
        return None

    file_name = os.path.basename(image_path)
    if not file_name:
        return None

    for allowed_dir in ALLOWED_IMAGE_DIRS:
        candidate_path = os.path.realpath(os.path.join(allowed_dir, file_name))
        if candidate_path.startswith(allowed_dir + os.sep) and os.path.exists(candidate_path):
            return candidate_path

    return None


@app.route('/api/encode-face', methods=['POST'])
def encode_face():
    try:
        data = request.json
        safe_image_path = get_safe_image_path(data.get('image_path'))
        if not safe_image_path:
            return jsonify({'error': 'Image path not provided'}), 400

        image = face_recognition.load_image_file(safe_image_path)
        locations = face_recognition.face_locations(image)

        if not locations:
            return jsonify({'error': 'No face detected'}), 400

        encodings = face_recognition.face_encodings(image, locations)
        if not encodings:
            return jsonify({'error': 'Encoding failed'}), 400

        face_encoding = encodings[0]

        return jsonify({
            'encoding': face_encoding.tolist(),
            'face_location': locations[0]
        })

    except Exception as e:
        print("ERROR:", e)
        return jsonify({'error': 'Internal server error'}), 500


@app.route('/api/recognize-face', methods=['POST'])
def recognize_face():
    try:
        data = request.json
        safe_image_path = get_safe_image_path(data.get('image_path'))

        print("REQUEST:", data)

        if not safe_image_path:
            return jsonify({'error': 'Image path not provided'}), 400

        known_encodings, known_ids = load_known_faces()

        if not known_encodings:
            return jsonify({'error': 'No registered faces'}), 404

        image = face_recognition.load_image_file(safe_image_path)
        locations = face_recognition.face_locations(image)

        if not locations:
            return jsonify({'error': 'No face detected'}), 400

        encodings = face_recognition.face_encodings(image, locations)

        if not encodings:
            return jsonify({'error': 'Encoding failed'}), 400

        face_encoding = encodings[0]

        distances = face_recognition.face_distance(known_encodings, face_encoding)

        best_index = np.argmin(distances)
        best_distance = distances[best_index]

        if best_distance < 0.6:
            matched_id = known_ids[best_index]
            confidence = (1 - best_distance) * 100

            print(f"Matched ID: {matched_id} ({confidence:.2f}%)")

            return jsonify({
                'matched': True,
                'id_karyawan': int(matched_id),
                'confidence': round(confidence, 2)
            })

        return jsonify({
            'matched': False,
            'message': 'Face not recognized'
        })

    except Exception as e:
        print("ERROR:", e)
        return jsonify({'error': 'Internal server error'}), 500


@app.route('/api/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok'})


if __name__ == '__main__':
    print("Face Recognition Service running on port 5000")
    app.run(host='0.0.0.0', port=5000, debug=True)
