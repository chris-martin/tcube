import sys
from urllib import urlencode
from urllib2 import Request, urlopen
from BeautifulSoup import BeautifulSoup
import re

from util import *

parser = arg_parser()
parser.add_argument('--page', required=True, help='id of the announcements page')
parser.add_argument('--site', help='id of the site (required if --id is given)')
parser.add_argument('--id', help='id of a particular announcement to fetch (default: fetch all)')
args = parser.parse_args()

output = { 'status': 500 }

def exit():
  print json_encode(output, args.pretty)
  sys.exit()

if args.verbose: output['log'] = []
def verbose(x):
  if args.verbose: output['log'].append(x)

if args.id:
  request = Request('https://t-square.gatech.edu/portal/tool/{0}' \
    '?itemReference=/announcement/msg/{1}/main/{2}&panel=Main' \
    '&sakai_action=doShowmetadata'.format(args.page, args.site, args.id))
else:
  request = Request('https://t-square.gatech.edu/portal/tool/{0}'.format(args.page),
    urlencode({ 'eventSubmit_doLinkcancel': 'Return to List' }))

add_cookieheader_from_json(request, args.cookies)
urlopen(request)

request = Request('https://t-square.gatech.edu/portal/tool/{0}'.format(args.page))
add_cookieheader_from_json(request, args.cookies)
response = urlopen(request).read()
verbose(response)

def scrape_index_html(html):
  return list(scrape_index_soup(BeautifulSoup(html)))

def scrape_index_soup(soup):
  for tr in soup.find('form', {'name': 'announcementListForm'}).findAll('tr'):
    x = scrape_index_row(tr)
    if x is not None: yield x

def scrape_index_row(tr):
  x = {}
  for p in 'subject author date'.split(' '):
    td = tr.find('td', headers = p)
    if td is None: return None
    x[p] = td.firstText(re.compile('\w')).strip()
  x['date'] = reformat_tsquare_datetime(x['date'])
  href = tr.find('td', headers = 'subject').find('a')['href']
  x['id'] = re.search('main/([a-z0-9\\-]+)&', href).group(1)
  return x

def scrape_get_html(html):
  x = { 'message': scrape_get_soup_message(BeautifulSoup(html)) }
  soup = BeautifulSoup(html)
  map = { 'Subject': 'subject', 'From': 'author', 'Date': 'date' }
  for tr in soup.find('div', attrs = {'class': 'portletBody'}).find('table').findAll('tr'):
    key = tr.find('th').string
    if key in map: x[map[key]] = tr.find('td').string
  if x['date'] == '$message.Header.Date.toStringLocalFull()':
    output['status'] = 404
    raise Exception('Announcement retrieval failed')
  x['date'] = reformat_tsquare_datetime(x['date'])
  return x

def scrape_get_soup_message(soup):
  x = soup.find('div', attrs = {'class': 'portletBody'})
  [ junk.extract() for junk in x.findAll('form') ]
  [ x.find(name).extract() for name in 'h3 h4 table'.split(' ') ]
  x.attrs = {}
  return str(x)

try:
  if args.id:
    output['announcement'] = scrape_get_html(response)
  else:
    output['announcements'] = scrape_index_html(response)
  output['status'] = 200

except Exception as e:
  verbose(e.message)

finally:
  exit()
