#!/bin/python3

import requests
from common import ConfigFile, key
from getpass import getpass

token = getpass('Acess token: ')
config = ConfigFile(key.config_name)

s = requests.Session()
s.headers['X-Auth'] = config[key.app_token]

page: requests.Response = s.get(f"{config[key.server]}/user")
if page.status_code != 200:
    print("Invalid app token")
    exit(1)
else:
    page = page.json()
    page_id = page['page_id']
    page_name = page['page_name']

result: requests.Response = requests.get(f"https://graph.facebook.com/v19.0/me", {
    'access_token': token
})

name = ''
if result.status_code != 200:
    print(result.json()['error']['message'])
    exit(1)
result = result.json()
name = result['name']

if result['id'] != page_id:
    print(f"Error: App token is for {page_name} but the access token is for {name}")
    exit(2)

result = s.post(f"{config[key.server]}/user", {
    'new_fb_token': token,
}, timeout=999)

if result.status_code != 200:
    print("Failed to update token")
else:
    print(f"Token for {page_name} has been updated")

