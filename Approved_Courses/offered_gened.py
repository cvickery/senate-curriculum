#! /usr/local/bin/python3

from pathlib import Path
from term_codes import term_code_to_name

available_pages = Path('./offered_gened/').glob('*.html')
last_change = 0
links = []
for link in available_pages:
  if link.stat().mtime > last_change:
    last_change = link.stat().mtime
  date_str = term_code_to_name(link.name[0:7])
  # Put the year at the beginning
  date_str = date_str[-4:] + ' ' + date_str[0:-5]
  links.append(f'<li><a href="{link}">{date_str}</a></li>')
links = '\n'.join(links)

last_update = date.fromtimestamp(last_change).strftime('%B %-d, %Y')

print(f"""Content-type: text/html\r\n\r\n
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8"/>
    <title>Scheduled GenEd Courses</title>
    <style>
      body {{
        font-family: sans-serif;
      }}
      h1, p {{
        text-align:center;
      }}
      ul {{
        list-style-type: none;
      }}
      li {{
        margin:0.5em;
      }}
      a {{
        text-decoration: none;
        font-size: 1.2em;
      }}
      a:hover {{
        text-decoration: underline;
        font-weight: bold;
      }}
    </style>
  </head>
  <body>
    <h1>Enrollment Information Is Available For The Following Terms</h1>
    <p>This list was last updated on {last_update}.</p>
    <ul>
    {links}
    </ul>
  </body>
</html>
""")
