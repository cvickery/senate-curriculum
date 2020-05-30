#! /usr/local/bin/python3
""" Currently active courses that have the WRIC attribute.
"""
import sys
import csv

from collections import namedtuple
from datetime import datetime
from pathlib import Path

gened_courses = Path('gened_courses.csv')
update_date = datetime.fromtimestamp(gened_courses.stat().st_mtime).strftime('%b %d, %Y')

table = ('<table><thead><tr><th>Course</th><th>Title</th><th>Pathways Area</th>'
         '<th>Writing Intensive / College Option</th><th>STEM Variant?</th></tr></thead><tbody>')
cols = None
with open(gened_courses) as csv_file:
  reader = csv.reader(csv_file)
  for line in reader:
    if cols is None:
      cols = [col.lower().replace(' ', '_') for col in line]
      Columns = namedtuple('Columns', cols)
      continue
    row = Columns._make(line)
    table += (f'<tr><td>{row.course}</td><td>{row.title}</td><td>{row.core}</td>'
              f'<td>{row.gened_attribute}</td><td>{row.stem_variant}</td></tr>')
table += '</tbody></table>'
table = table.replace('&', '&amp;')
print('Content-Type: text/html; charset=UTF-8\r\n\r\n')
html_page = f"""<!DOCTYPE html>
  <html>
    <head>
      <title>QC GenEd Courses</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="./css/gened_courses.css" />
    </head>
    <body>
      <h1>Active Queens College General Education Courses</h1>
      <h2>List was last updated {update_date}</h2>
      <p>Writing Intensive courses have WRIC in the fourth column; College Option courses have
      QNSLIT (Literature), QNSLANG (Language), QNSSCI (Science), or QNSSYN (“Synthesis,” but
      normally called “Additional.”)</p>
      <p>STEM Variant courses are courses which exceed the Pathways limit of 3 credits, but which
      can be used to satisfy a requirement for a major. Some STEM Variants can be used to satisfy
      the MQR requirement, others can be used for either LPS or SW. Ones that can satisfy SW could
      be used to satisfy QNSSCI instead.</p>
      <p>You can <a href="./gened_courses.csv" download>download this table as a spreadsheet</a>.
      </p>
      {table}
    </body>
  </html>"""
print(html_page)
