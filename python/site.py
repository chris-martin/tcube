import sys
from urllib2 import Request, urlopen, HTTPError
from datetime import datetime

from util import *

parser = arg_parser()
parser.add_argument('--id', help='id of a particular site to fetch (default: fetch all)')
args = parser.parse_args()

output = { 'status': 500 }

def exit():
  print json_encode(output, args.pretty)
  sys.exit()

if args.verbose: output['log'] = []
def verbose(x):
  if args.verbose: output['log'].append(x)

try:

  if args.id:
    request = Request('https://t-square.gatech.edu/direct/site/{0}.json'.format(args.id))
    add_cookieheader_from_json(request, args.cookies)
    try:
      response = urlopen(request).read()
    except HTTPError as e:
      if e.code == 404:
        output['status'] = 404
      raise e
  else:
    request = Request('https://t-square.gatech.edu/direct/site.json')
    add_cookieheader_from_json(request, args.cookies)
    response = urlopen(request).read()

  verbose(response)
  root = json_decode(response)

  def transform(x):
    return {
      'id': x['entityId'],
      'title': x['title'],
      'type': x['type'],
      'lastModified': format_datetime(datetime.fromtimestamp(int(x['lastModified']) / 1000))
    }

  if args.id:
    output['site'] = transform(root)
  else:
    output['sites'] = map(transform, root['site_collection'])

  output['status'] = 200

except Exception as e:
  verbose(e.message)

finally:
  exit()
