#! /usr/local/bin/python3
# Provide a page for downloading copies of the latest queries for building the cuny_courses
# database. Part of the "Transition to Coursedog" project.

from pathlib import Path
from datetime import datetime

print('Content-Type: text/html; charset=UTF-8\r\n\r\n')

print("""
<!DOCTYPE html>
<html>
  <head>
    <title>CUNYfirst Course Information</title>
    <style>
      li {
      white-space:pre;
      font-family: monospace;
      }
    </style>
  </head>
  <body>
      """)
suffixes = ' KMGTP'
latest_queries = Path('./latest_queries')
update_date = datetime.fromtimestamp(latest_queries.stat().st_mtime).strftime('%B %e, %Y')
print(f'<h1>CUNYfirst Course Information</h1>\n<p>Last updated {update_date}.</p><ul>')
for query in latest_queries.glob('*.csv'):
  bytes = query.stat().st_size
  power = 0
  while bytes > 1000:
    bytes /= 1000
    power += 1
  print(f'<li>{bytes:>5.1f}{suffixes[power]}B <a href="{query}" download>{query.name}</a></li>')

print('</ul></body></html>')
