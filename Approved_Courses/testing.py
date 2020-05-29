#! /usr/local/bin/python3
""" Currently active courses that have the WRIC attribute.
"""
import sys
import csv
from datetime import datetime
from pathlib import Path

gened_courses = Path('gened_courses.csv')
table = '<table><tr><th>Course</th><th>Title</th><th>Common Core</th><th>College Option</th></tr>'
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
              f'<td>{row.stem_variant}</td><td>{row.gened_attributes}</td></tr>')
table += '</table>'
table = table.replace('&', '&amp;')
print('Content-Type: text/html; charset=UTF-8\r\n\r\n')
html_page = f"""
  <!DOCTYPE html>
  <html>
    <head>
      <title>Active Writing Intensive Courses</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="./css/writing_intensive.css" />
    </head>
    <body>
      <h1>Active Writing Intensive Courses at Queens College</h1>
      <h2>List was last updated {update_date}</h2>
      {table}
    </body>
  </html>"""
print(html_page)
