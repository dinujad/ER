#!/usr/bin/env python3
"""
Build a ZIP with Unix permission bits so cPanel/Linux extract
gets 755/644 (and 775/664 for storage + bootstrap/cache).
No post-upload fix-permissions URL needed when host honors ZIP attrs.
"""
from __future__ import annotations

import os
import sys
import time
import zipfile
from pathlib import Path


def mode_for(rel: str, is_dir: bool) -> int:
    rel = rel.replace("\\", "/").lstrip("./")
    writable_roots = ("storage/", "bootstrap/cache/")
    under_writable = any(
        rel == r.rstrip("/") or rel.startswith(r) for r in writable_roots
    )
    if is_dir:
        return 0o775 if under_writable else 0o755
    if under_writable:
        return 0o664
    name = Path(rel).name
    if name in ("artisan", "fix-permissions.sh") or rel.endswith(".sh"):
        return 0o755
    return 0o644


def external_attr(is_dir: bool, mode: int) -> int:
    file_type = 0o040000 if is_dir else 0o100000
    return (file_type | (mode & 0o7777)) << 16


def zip_datetime(path: Path) -> tuple[int, int, int, int, int, int]:
    try:
        return time.localtime(path.stat().st_mtime)[:6]
    except OSError:
        return time.localtime()[:6]


def add_path(zf: zipfile.ZipFile, root: Path, path: Path) -> None:
    rel = path.relative_to(root).as_posix()
    is_dir = path.is_dir()
    arc = (rel + "/") if is_dir else rel
    mode = mode_for(rel, is_dir)

    info = zipfile.ZipInfo(filename=arc, date_time=zip_datetime(path))
    info.create_system = 3  # Unix
    info.external_attr = external_attr(is_dir, mode)

    if is_dir:
        zf.writestr(info, b"")
        return

    info.compress_type = zipfile.ZIP_DEFLATED
    with open(path, "rb") as fh:
        zf.writestr(info, fh.read())


def main() -> int:
    if len(sys.argv) != 3:
        print("Usage: make-unix-zip.py <staging_dir> <output_zip>", file=sys.stderr)
        return 2

    root = Path(sys.argv[1]).resolve()
    out = Path(sys.argv[2]).resolve()
    if not root.is_dir():
        print(f"Staging not found: {root}", file=sys.stderr)
        return 1

    if out.exists():
        out.unlink()

    count_dirs = 0
    count_files = 0

    with zipfile.ZipFile(out, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
        for dirpath, dirnames, filenames in os.walk(root):
            dirnames.sort()
            filenames.sort()
            base = Path(dirpath)

            if base != root:
                add_path(zf, root, base)
                count_dirs += 1

            for name in filenames:
                add_path(zf, root, base / name)
                count_files += 1

    size_mb = out.stat().st_size / (1024 * 1024)
    print(f"Created: {out}")
    print(f"Entries: {count_dirs} dirs, {count_files} files ({size_mb:.1f} MB)")
    print("Unix modes: dirs 755 (storage/bootstrap/cache 775), files 644 (writable 664), scripts 755")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
