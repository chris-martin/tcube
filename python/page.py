import sys
from urllib2 import Request, urlopen
import itertools

from util import *

parser = arg_parser()
parser.add_argument('--site', required=True, help='id of the site from which to fetch pages')
args = parser.parse_args()

output = { 'status': 500 }

def exit():
  print json_encode(output, args.pretty)
  sys.exit()

if args.verbose: output['log'] = []
def verbose(x):
  if args.verbose: output['log'].append(x)

def transform(x):
  return {
    'id': x['id'],
    'tool': x['toolId']
  }

try:

  request = Request('https://t-square.gatech.edu/direct/site/{0}/pages.json'.format(args.site))
  add_cookieheader_from_json(request, args.cookies)
  response = urlopen(request).read()
  verbose(response)
  root = json_decode(response)
  pages = itertools.chain.from_iterable(map(lambda x: x['tools'], root))
  output['pages'] = map(transform, pages)
  output['status'] = 200

except Exception as e:
  verbose(e.message)

finally:
  exit()
