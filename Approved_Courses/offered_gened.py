#! /usr/local/bin/python3

from pathlib import Path
from datetime import date
from term_codes import term_code_to_name

available_pages = Path('./offered_gened/').glob('*.html')
last_change = 0
links = []
last_year = ''
for link in available_pages:
  if link.stat().st_mtime > last_change:
    last_change = link.stat().st_mtime
  date_str = term_code_to_name(link.name[0:7])
  this_year = date_str[-4:]
  if this_year != last_year:
    if last_year == '':
      links.append('<ul>')
    last_year = this_year
    links = links + ['</ul>', '<hr>', '<ul>']
  # Put the year at the beginning
  date_str = this_year + ' ' + date_str[0:-5]
  links.append(f'<li><a href="{link}">{date_str}</a></li>')
links.pop()  # drop extra ul at end
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
        font-family: "Cooper Black", sans-serif;
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
        font-weight: bold;
      }}
      a:hover {{
        text-decoration: underline;
        font-weight: bold;
        font-size: 1.05em
      }}
    </style>
  </head>
  <body>
    <h1>Scheduled General Education Courses</h1>
    <p>A list of all currently-active QC courses that have been approved as GenEd (Pathways) courses
      is avalable at <a href="./gened_courses.py">Approved GenEd Courses</a></p>
    <p>This list was last updated on {last_update}</p>
    {links}
  </body>
</html>
""")
