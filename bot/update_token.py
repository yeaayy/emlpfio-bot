#!/bin/python3

import requests
from common import ConfigFile, key
from getpass import getpass

token = getpass('Acess token: ')
config = ConfigFile(key.config_name)

result: requests.Response = requests.get(f"https://graph.facebook.com/v19.0/me", {
    'access_token': token
})

name = ''
if result.status_code != 200:
    print(result.json()['error']['message'])
    exit(1)
else:
    name = result.json()['name']

requests.post(f"{config[key.server]}/user", {
    'new_fb_token': token,
}, headers={
    'X-Auth': config[key.app_token],
}, timeout=999)

if result.status_code != 200:
    print("Failed to update token")
else:
    print(f"Token for {name} has been updated")

