#!/usr/bin/env python3
"""One-shot import rewriter after src/ tree reorganization."""
from __future__ import annotations

import os
import re
from pathlib import Path

ROOT = Path("src")
TEXT_EXT = {".ts", ".tsx", ".js", ".jsx", ".scss", ".css", ".html", ".json"}

IMPORT_RE = re.compile(
    r"""(?P<prefix>import\s+(?:type\s+)?(?:[\w*{}\s,]+\s+from\s+)?|export\s+.*?\sfrom\s+|require\s*\(\s*)['"](?P<path>[^'"]+)['"]"""
)


def rewrite_import(file_path: Path, imp: str) -> str:
    fp = file_path.as_posix()
    new = imp

    if "common/data" in new:
        new = new.replace("common/data", "template/data")
    if fp.startswith("src/template/pages/"):
        new = new.replace("template/data", "data")

    if fp.startswith("src/app/routes/"):
        new = new.replace("../pages/", "../template/pages/")
        new = new.replace("./pages/", "./template/pages/")

    if "/pages/" in new and "template/pages" not in new:
        if any(x in new for x in ("../pages/", "../../pages/", "../../../pages/")):
            new = new.replace("/pages/", "/template/pages/")

    for old, rep in (
        ("../Components/", "../shared/components/"),
        ("../../Components/", "../../shared/components/"),
        ("../../../Components/", "../../../shared/components/"),
        ("../../../../Components/", "../../../../shared/components/"),
        ("../../../../../Components/", "../../../../../shared/components/"),
        ("../Layouts/", "../shared/layouts/"),
        ("../../Layouts/", "../../shared/layouts/"),
        ("../../../Layouts/", "../../../shared/layouts/"),
        ("../helpers/", "../shared/helpers/"),
        ("../../helpers/", "../../shared/helpers/"),
        ("../../../helpers/", "../../../shared/helpers/"),
    ):
        if old in imp:
            new = new.replace(old, rep)

    if new.startswith("Components/"):
        new = "shared/components/" + new[len("Components/") :]
    if new.startswith("Layouts/"):
        new = "shared/layouts/" + new[len("Layouts/") :]
    if new.startswith("helpers/"):
        new = "shared/helpers/" + new[len("helpers/") :]

    new = new.replace("../../../config/branding", "../../../shared/config/branding")
    new = new.replace("../../config/branding", "../../shared/config/branding")
    new = new.replace("../config/branding", "../shared/config/branding")
    new = new.replace("/zakat/", "/features/")
    if new.startswith("zakat/"):
        new = "features/" + new[6:]

    if fp.startswith("src/shared/layouts/") and imp.startswith("../assets/"):
        new = "../../assets/" + imp[len("../assets/") :]
    if fp.startswith("src/shared/layouts/") and imp.startswith("../slices/"):
        new = "../../slices/" + imp[len("../slices/") :]
    if fp.startswith("src/shared/layouts/") and "../Components/" in imp:
        new = imp.replace("../Components/", "../components/")

    if fp.startswith("src/shared/components/") and imp.startswith("../slices/"):
        new = "../../slices/" + imp[len("../slices/") :]

    if fp.startswith("src/features/auth/pages/"):
        if imp.startswith("../../../assets/"):
            new = "../../../../assets/" + imp[len("../../../assets/") :]
        if "../../../pages/" in imp:
            new = imp.replace("../../../pages/", "../../../template/pages/")
        new = new.replace("../../auth/", "../")
        new = new.replace("../../api/", "../../api/")

    if fp.startswith("src/template/pages/") and "Components/" in imp:
        suffix = imp.split("Components/", 1)[1]
        target = ROOT / "shared/components" / suffix
        rel = os.path.relpath(target, file_path.parent).replace("\\", "/")
        new = rel if rel.startswith(".") else "./" + rel

    if fp.startswith("src/template/pages/") and "Layouts/" in imp:
        suffix = imp.split("Layouts/", 1)[1]
        target = ROOT / "shared/layouts" / suffix
        rel = os.path.relpath(target, file_path.parent).replace("\\", "/")
        new = rel if rel.startswith(".") else "./" + rel

    if fp == "src/index.tsx" and imp == "./App":
        new = "./app/App"
    if fp == "src/app/App.tsx":
        if imp == "./Routes":
            new = "./routes"
        if imp.startswith("./helpers/"):
            new = "./shared/helpers/" + imp[len("./helpers/") :]

    if fp == "src/app/routes/index.tsx" and imp.startswith("../Layouts/"):
        new = imp.replace("../Layouts/", "../shared/layouts/")
    if fp == "src/app/routes/AuthProtected.tsx":
        if imp.startswith("../helpers/"):
            new = "../../shared/helpers/" + imp[len("../helpers/") :]
        if imp.startswith("../Components/"):
            new = "../../shared/components/" + imp[len("../Components/") :]

    if fp.startswith("src/slices/") and imp.startswith("../helpers/"):
        new = "../shared/helpers/" + imp[len("../helpers/") :]

    if any(
        fp.startswith(p)
        for p in (
            "src/features/users/",
            "src/features/roles/",
            "src/features/organizations/",
        )
    ):
        new = new.replace("../../auth/", "../auth/")
        new = new.replace("../../components/", "../components/")
        new = new.replace("../../hooks/", "../hooks/")
        new = new.replace("../../api/", "../api/")

    return new


def main() -> None:
    changed_files = 0
    changed_imports = 0

    for path in ROOT.rglob("*"):
        if not path.is_file() or path.suffix not in TEXT_EXT:
            continue

        text = path.read_text(errors="ignore")
        original = text

        def repl(match: re.Match[str]) -> str:
            nonlocal changed_imports
            old_path = match.group("path")
            new_path = rewrite_import(path, old_path)
            if new_path != old_path:
                changed_imports += 1
            return f'{match.group("prefix")}"{new_path}"'

        text = IMPORT_RE.sub(repl, text)

        if path.as_posix().startswith("src/template/pages/"):
            text = text.replace("template/data", "data")

        text = text.replace("from 'common/data", "from 'template/data")
        text = text.replace('from "common/data', 'from "template/data')

        if text != original:
            path.write_text(text)
            changed_files += 1

    print(f"Updated {changed_files} files, {changed_imports} import paths")


if __name__ == "__main__":
    main()
