from BeautifulSoup import BeautifulSoup as Soup
from getpass import getpass
from mechanize import Browser, CookieJar
import random
import re
import string
import sys

from util import *

parser = arg_parser(cookies = False)
parser.description = 'Logs into T-Square. ' \
  'Outputs cookies and timeout duration in json format. ' \
  'Also generates a random session id.'
parser.add_argument('--username', required=True, help='username for CAS authentication')
parser.add_argument('--password', help='password for CAS authentication')
args = parser.parse_args()

output = { 'status': 500 }

alphabet = string.letters[0:52] + string.digits
output['session_id'] = ''.join(random.SystemRandom().choice(alphabet) for _ in range(32))

def exit():
  print json_encode(output, args.pretty)
  sys.exit()

if args.verbose: output['log'] = []
def verbose(x):
  if args.verbose: output['log'].append(x)

if args.password is None: args.password = getpass('CAS password for {0}:'.format(args.username))

try:

  cookiejar = CookieJar()
  browser = Browser()
  browser.set_cookiejar(cookiejar)

  verbose('Opening CAS login page')
  response = browser.open('https://login.gatech.edu/cas/login')
  verbose(response.get_data())
  browser.select_form(predicate = lambda f: 'id' in f.attrs and f.attrs['id'] == 'fm1')
  browser['username'] = args.username
  browser['password'] = args.password

  verbose('Logging into CAS')
  response = browser.submit()
  verbose(response.get_data())
  soup = Soup(response.get_data())
  success = soup.firstText(re.compile('(.*)log(.*)success(.*)', re.IGNORECASE)) is not None
  if not success:
    output['status'] = 401
    output['errors'] = map(lambda x: x.string, soup.findAll(attrs={'class': 'errors'}))
    raise Exception("Login failed")

  verbose('Logging into T-Square')
  response = browser.open('https://t-square.gatech.edu')
  verbose(response.get_data())

  cookies = cookiejar_to_list(cookiejar)
  output['cookies'] = cookies
  verbose(cookies)

  verbose('Loading session info')
  response = browser.open('https://t-square.gatech.edu/direct/session/current.json')
  verbose(response.get_data())
  session_info = json_decode(response.get_data())
  output['timeout'] = session_info['maxInactiveInterval']

  output['status'] = 200

except Exception as e:
  verbose(e.message)

finally:
  exit()
