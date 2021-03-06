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
      body {
      padding: 1em;
      font-family: sans-serif;
      }
      li {
      list-style-type: none;
      white-space: pre;
      font-family: monospace;
      }
      a {
      text-decoration: none;
      }
      a:hover {
      font-size: 1.1em;
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
  print(f'<li>{bytes:>5.1f} {suffixes[power]}B <a href="{query}" download>{query.name}</a></li>')
other_resources = Path('./other_resources')
update_date = datetime.fromtimestamp(other_resources.stat().st_mtime).strftime('%B %e, %Y')
print(f'</ul><h1>Other Resources</h1>\n<p>Last updated {update_date}</p><ul>')
for resource in other_resources.glob('*'):
  bytes = resource.stat().st_size
  power = 0
  while bytes > 1000:
    bytes /= 1000
    power += 1
  print(f'<li>{bytes:>5.1f} {suffixes[power]}B <a href="{resource}" download>{resource.name}</a></li>')
print('</ul></body></html>')
