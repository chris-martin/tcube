import sys
from urllib import urlencode
from urllib2 import Request, urlopen
from BeautifulSoup import BeautifulSoup
import re

from util import *

parser = arg_parser()
parser.add_argument('--page', required=True, help='id of the assignments page')
parser.add_argument('--id', help='id of a particular assignment to fetch (default: fetch all)')
args = parser.parse_args()

output = { 'status': 500 }

def exit():
  print json_encode(output, args.pretty)
  sys.exit()

if args.verbose: output['log'] = []
def verbose(x):
  if args.verbose: output['log'].append(x)

main_url = 'https://t-square.gatech.edu/portal/tool/{0}'.format(args.page)

def list_soup():

  # load the assignment tool. It might be in the listing state. It might not be.
  request = Request(main_url)
  add_cookieheader_from_json(request, args.cookies)
  response = urlopen(request).read()
  verbose(response)
  soup = BeautifulSoup(response)

  # if there is a cancel button on the page, then click it to get back to the listing state.
  cancel_button = soup.find('input', attrs = {'name': re.compile('^eventSubmit_doCancel_')})
  if cancel_button is not None:
    request = Request(main_url, urlencode({cancel_button['name']: cancel_button['value']}))
    add_cookieheader_from_json(request, args.cookies)
    response = urlopen(request).read()
    verbose(response)
    soup = BeautifulSoup(response)

  return soup

def scrape_index(soup):
  for tr in soup.find('form', {'name': 'listAssignmentsForm'}).findAll('tr'):
    x = scrape_index_row(tr)
    if x is not None: yield x

def scrape_index_row(tr):
  x = {}
  for p in 'title status openDate dueDate'.split(' '):
    td = tr.find('td', headers = p)
    if td is None: return None
    x[p] = td.firstText(re.compile('\w')).strip()
  for p in 'openDate dueDate'.split(' '):
    x[p] = reformat_tsquare_datetime(x[p])
  href = tr.find(headers = 'title').find('a')['href']
  x['id'] = re.search('/assignment/(?:[^/]+/){2}([^/&]+)[/&]', href).group(1)
  return x

try:

  soup = list_soup()

  if not args.id:
    output['assignments'] = list(scrape_index(soup))

  else:
    form = soup.find('form', attrs = {'name': 'listAssignmentsForm'})
    anchor = form.find('a', href = re.compile(args.id))
    assignment = scrape_index_row(anchor.findParent('tr'))
    request = Request(anchor['href'])
    add_cookieheader_from_json(request, args.cookies)
    response = urlopen(request).read()
    verbose(response)
    soup = BeautifulSoup(response)

    instructions = soup.find(attrs = {'class': 'textPanel'})
    if instructions:
      instructions.attrs = {}
      assignment['instructions'] = str(instructions)

    table = soup.find(attrs = {'class': 'itemSummary'})
    if table:
      for tr in table.findAll('tr'):
        key = tr.find('th').firstText(re.compile('\w')).strip()
        value = tr.find('td').firstText(re.compile('\w')).strip()
        if re.compile('^grade scale:?$', re.I).match(key):
          assignment['grade_scale'] = value
        if re.compile('^grade:?$', re.I).match(key):
          assignment['grade'] = value

    output['assignment'] = assignment

  output['status'] = 200

except Exception as e:
  verbose(e.message)

finally:
  exit()
