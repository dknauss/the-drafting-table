#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="${ROOT_DIR}/dist/wporg"
OUT_THEME_DIR="${OUT_DIR}/the-drafting-table"
OUT_ZIP="${OUT_DIR}/the-drafting-table-wporg.zip"
TMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/the-drafting-table-wporg.XXXXXX")"
BUILD_DIR="${TMP_DIR}/the-drafting-table"

cleanup() {
	rm -rf "${TMP_DIR}"
}
trap cleanup EXIT

mkdir -p "${OUT_DIR}" "${BUILD_DIR}" "${OUT_THEME_DIR}"

rsync -a \
	--exclude-from="${ROOT_DIR}/.distignore" \
	--exclude-from="${ROOT_DIR}/.distignore.wporg" \
	"${ROOT_DIR}/" "${BUILD_DIR}/"

rsync -a --delete "${BUILD_DIR}/" "${OUT_THEME_DIR}/"

(
	cd "${TMP_DIR}"
	zip -qr "${OUT_ZIP}" the-drafting-table
)

echo "WP.org build ready: ${OUT_THEME_DIR}"
echo "WP.org zip ready: ${OUT_ZIP}"
