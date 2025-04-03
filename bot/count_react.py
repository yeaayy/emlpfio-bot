#!/bin/python3

import os
from common import ConfigFile, key
import requests
import numbers

if len(os.sys.argv) < 3:
    print(f"Usage: {os.sys.argv[0]} <show> <group> [start=1]")
    exit(1)

show_name = os.sys.argv[1]
group_name = os.sys.argv[2]
if len(os.sys.argv) > 3:
    start = int(os.sys.argv[3])
else:
    start = 1
step = 50

config = ConfigFile(key.config_name)
s = requests.Session()
s.headers['x-auth'] = config[key.app_token]

API_FETCH_REACTS = f"{config[key.server]}/show/{show_name}/group/{group_name}/fetch_reacts"

# Get frame count
result: requests.Response = s.get(f"{config[key.server]}/show/{show_name}/group/{group_name}/frame_count")
if result.status_code != 200:
    print(f"{show_name}-{group_name} not found")
    exit(1)
result = result.json()
total_frame = result['count']
print(f"{show_name}-{group_name} total frame = {total_frame}")

i = start
fails = []
while (i <= total_frame):
    print(f"Fetching {i}-{i + step - 1}")
    result: requests.Response = s.post(API_FETCH_REACTS, {
        'start': i,
    }, timeout=999)
    if result.status_code == 200:
        for (frame, result) in result.json().items():
            if not isinstance(result, numbers.Number):
                print(f"{frame} fail {result}")
                fails.append(frame)
        i += step
    else:
        print(f"Error {result.status_code}")

# Retrying
while len(fails) > 0:
    frame = fails[0]
    print(f"Retrying {frame}")
    result: requests.Response = s.post(API_FETCH_REACTS, {
        'start': frame,
        'count': 1,
    }, timeout=999)
    if result.status_code == 200:
        for (frame, result) in result.json().items():
            if isinstance(result, numbers.Number):
                fails.pop(0)
            else:
                print(f"{frame} fail {result}")
