#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if command -v cygpath >/dev/null 2>&1; then
    ROOT_WIN="$(cygpath -m "$ROOT_DIR")"
else
    ROOT_WIN="$ROOT_DIR"
fi

LARAVEL_HOST="${LARAVEL_HOST:-127.0.0.1}"
LARAVEL_PORT="${LARAVEL_PORT:-8000}"
FACE_SERVICE_HOST="${FACE_SERVICE_HOST:-127.0.0.1}"
FACE_SERVICE_PORT="${FACE_SERVICE_PORT:-5000}"

export FACE_SERVICE_URL="${FACE_SERVICE_URL:-http://localhost:${FACE_SERVICE_PORT}}"
export FACE_TEMP_DIR="${FACE_TEMP_DIR:-${ROOT_WIN}/storage/app/temp}"
export ALLOWED_IMAGE_TMP_DIR="${ALLOWED_IMAGE_TMP_DIR:-$FACE_TEMP_DIR}"

mkdir -p "$FACE_TEMP_DIR"

if command -v py >/dev/null 2>&1; then
    PYTHON_CMD=(py -3)
elif command -v python >/dev/null 2>&1; then
    PYTHON_CMD=(python)
else
    echo "Python tidak ditemukan. Install Python atau pastikan command 'py'/'python' tersedia di PATH."
    exit 1
fi

cleanup() {
    echo
    echo "Stopping services..."
    kill "${LARAVEL_PID:-}" "${PYTHON_PID:-}" >/dev/null 2>&1 || true
}
trap cleanup INT TERM EXIT

echo "FACE_SERVICE_URL=$FACE_SERVICE_URL"
echo "FACE_TEMP_DIR=$FACE_TEMP_DIR"
echo "ALLOWED_IMAGE_TMP_DIR=$ALLOWED_IMAGE_TMP_DIR"
echo

cd "$ROOT_DIR"
php artisan config:clear >/dev/null 2>&1 || true
php artisan serve --host="$LARAVEL_HOST" --port="$LARAVEL_PORT" &
LARAVEL_PID=$!

(
    cd "$ROOT_DIR/face_recognition_service"
    "${PYTHON_CMD[@]}" face_recognition_service.py
) &
PYTHON_PID=$!

echo "Laravel: http://${LARAVEL_HOST}:${LARAVEL_PORT}"
echo "Face service: http://${FACE_SERVICE_HOST}:${FACE_SERVICE_PORT}"
echo "Tekan Ctrl+C untuk stop."

wait "$LARAVEL_PID" "$PYTHON_PID"
