#!/usr/bin/env python3
"""
Export requirement rows from REFACTORED_REQUIREMENTS.md into a CSV file.
Only include actual table rows (not headers or separator lines).

CSV columns: Package/App (Namespace), Requirement #, Description,
Implemented in (Class / File / Method), Status, Notes, Date

This script will:
- Read REFACTORED_REQUIREMENTS.md
- Find all lines that look like table rows (start with '|')
- Skip header rows (lines that include '---' or the standard header text)
- Parse the table columns by splitting on '|' and trimming
- Output a CSV `requirements_export.csv` in repo root

Usage: python scripts/export_requirements.py
"""
import csv
import re
from pathlib import Path

root = Path(__file__).resolve().parents[1]
md_file = root / 'REFACTORED_REQUIREMENTS.md'
out_file = root / 'requirements_export.csv'

if not md_file.exists():
    print('File not found:', md_file)
    raise SystemExit(1)

rows = []
with md_file.open('r', encoding='utf-8') as f:
    for line in f:
        line = line.rstrip('\n')
        if not line.startswith('|'):
            continue
        # Skip separator rows like '| --- | --- |'
        if re.match(r"^\|\s*-{3,}" , line):
            continue
        # Skip header label lines that contain 'Package/App' or other header text
        header_keywords = ['Package/App (Namespace)', 'Requirement #', 'Description', 'Implemented in']
        if any(h in line for h in header_keywords):
            continue
        # Now this is a table row -- split by '|' and strip spaces
        parts = [col.strip() for col in line.split('|')]
        # split produces empty strings at beginning and end due to leading/trailing pipe
        if parts and parts[0] == '':
            parts = parts[1:]
        if parts and parts[-1] == '':
            parts = parts[:-1]
        # Some rows might be broken across lines (we assume markdown is not broken)
        if len(parts) < 7:
            # Try to pad to 7 columns with empty values
            parts += [''] * (7 - len(parts))
        # Keep only the first 7 columns
        row = parts[:7]
        # Remove backticks
        row = [re.sub(r'`', '', c) for c in row]
        rows.append(row)

# Write CSV
with out_file.open('w', encoding='utf-8', newline='') as csvfile:
    writer = csv.writer(csvfile)
    # header
    writer.writerow(['Package/App (Namespace)', 'Requirement #', 'Description', 'Implemented in', 'Status', 'Notes', 'Date'])
    for r in rows:
        writer.writerow(r)

print('Wrote', out_file)
