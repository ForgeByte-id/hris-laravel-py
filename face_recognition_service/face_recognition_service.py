from flask import Flask, request, jsonify
from flask_cors import CORS
import face_recognition
import numpy as np
import os
import json
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv

# load env
load_dotenv()

app = Flask(__name__)
CORS(app)

# ======================
# DB CONFIG FROM ENV
# ======================
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'mysql'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'database': os.getenv('DB_DATABASE', 'hris_db'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', 'root')
}

def get_db_connection():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        return connection
    except Error as e:
        print(f"Error connecting to database: {e}")
        return None

def load_known_faces():
    """Load face encodings dari kolom face_embedding tabel karyawan"""
    connection = get_db_connection()
    if not connection:
        return [], []

    try:
        cursor = connection.cursor(dictionary=True)
        # Query dari tabel karyawan dengan kolom face_embedding
        cursor.execute(
            "SELECT id_karyawan, face_embedding FROM karyawan WHERE face_embedding IS NOT NULL"
        )
        results = cursor.fetchall()

        known_face_encodings = []
        known_face_ids = []

        for row in results:
            encoding = json.loads(row['face_embedding'])
            known_face_encodings.append(np.array(encoding))
            known_face_ids.append(row['id_karyawan'])

        print(f"Loaded {len(known_face_ids)} registered faces from database")
        return known_face_encodings, known_face_ids

    except Error as e:
        print(f"Error loading faces: {e}")
        return [], []

    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

@app.route('/api/encode-face', methods=['POST'])
def encode_face():
    try:
        data = request.json
        image_path = data.get('image_path')

        if not image_path or not os.path.exists(image_path):
            return jsonify({'error': 'Image not found'}), 400

        image = face_recognition.load_image_file(image_path)
        face_locations = face_recognition.face_locations(image)

        if len(face_locations) == 0:
            return jsonify({'error': 'No face detected'}), 400

        if len(face_locations) > 1:
            return jsonify({
                'error': 'Multiple faces detected. Use single face.'
            }), 400

        face_encodings = face_recognition.face_encodings(
            image,
            face_locations
        )

        if len(face_encodings) == 0:
            return jsonify({'error': 'Unable to encode face'}), 400

        encoding_list = face_encodings[0].tolist()

        return jsonify({
            'success': True,
            'encoding': encoding_list,
            'face_location': face_locations[0]
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/recognize-face', methods=['POST'])
def recognize_face():
    try:
        data = request.json
        image_path = data.get('image_path')

        if not image_path or not os.path.exists(image_path):
            return jsonify({'error': 'Image not found'}), 400

        known_face_encodings, known_face_ids = load_known_faces()

        if len(known_face_encodings) == 0:
            return jsonify({'error': 'No registered faces in database'}), 404

        image = face_recognition.load_image_file(image_path)
        face_locations = face_recognition.face_locations(image)

        if len(face_locations) == 0:
            return jsonify({'error': 'No face detected'}), 400

        face_encodings = face_recognition.face_encodings(
            image,
            face_locations
        )

        if len(face_encodings) == 0:
            return jsonify({'error': 'Unable to encode face'}), 400

        face_encoding = face_encodings[0]

        face_distances = face_recognition.face_distance(
            known_face_encodings,
            face_encoding
        )

        best_match_index = np.argmin(face_distances)
        best_match_distance = face_distances[best_match_index]

        threshold = 0.6

        if best_match_distance < threshold:
            matched_id = known_face_ids[best_match_index]
            confidence = (1 - best_match_distance) * 100

            print(f"Face matched! ID: {matched_id}, Confidence: {confidence:.2f}%")

            return jsonify({
                'matched': True,
                'id_karyawan': int(matched_id),
                'confidence': round(confidence, 2),
                'distance': round(float(best_match_distance), 4)
            })
        else:
            print(f"Face not recognized. Best distance: {best_match_distance:.4f}")
            return jsonify({
                'matched': False,
                'message': 'Face not recognized',
                'best_distance': round(float(best_match_distance), 4)
            })

    except Exception as e:
        print(f"Error in recognize_face: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/health', methods=['GET'])
def health_check():
    return jsonify({
        'status': 'healthy',
        'service': 'Face Recognition API'
    })

if __name__ == '__main__':
    print("=" * 50)
    print("Starting Face Recognition Service...")
    print("Service running on http://localhost:5000")
    print("=" * 50)
    app.run(host='0.0.0.0', port=5000, debug=True)
