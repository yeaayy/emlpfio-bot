#!/bin/python3

import asyncio
import ffmpeg
import io
import json
import os
import telegram
import time
import requests
from PIL import Image
from telegram import Message
from telegram.error import TimedOut, RetryAfter, NetworkError

if len(os.sys.argv) != 4:
    print(f"Usage: {os.sys.argv[0]} <show> <group> <video>")
    exit(1)

def check_group():
    global start_frame
    result = session.get(f"{bot_server}/show/{show_name}/group/{group_name}/first_empty").json()
    if 'error' in result:
        print(f"Error {show_name}-{group_name}: {result['error']}")
        exit(1)
    else:
        start_frame = result['count']

def save_config():
    with open(config_name, 'w') as file:
        json.dump(config, file, indent=4)

async def store(file_data, frame_index):
    # Check if frame already exist
    check = session.get(f"{bot_server}/show/{show_name}/group/{group_name}/frame/{frame_index}")
    if check.status_code == 200:
        print(f'{show_name}.{group_name}.{frame_index} exist skipping...')
        return

    filename = f"{show_name}.{group_name}.{frame_index}.bin"
    tries_count = 0
    while True:
        try:
            result: Message = await bot.send_document(
                chat_id = config['dst_id'], document=file_data, filename=filename,
                connect_timeout=300,
                read_timeout=300,
                write_timeout=300,
                pool_timeout=300,
            )
        except (TimedOut, NetworkError) as e:
            tries_count += 1
            print(f"{filename} Retrying ({tries_count})... {type(e)}:{e.message}")
            time.sleep(5)
        except RetryAfter as e:
            print(f"{filename} Retrying after {e.retry_after};")
            time.sleep(e.retry_after)
        else:
            break
    print()
    print(f"{filename} ok")
    doc = result.document
    store_result: requests.Response = session.post(f"{bot_server}/show/{show_name}/group/{group_name}/frame", {
        'frame': frame_index,
        'file': doc.file_id,
    })
    store_data = store_result.json()
    if store_result.status_code != 200:
        print('msg_id:', result.id)
        print(f"Error {store_result.status_code}: {store_data['error']}")
        print(f"Frame index : {frame_index}")
        print(f"File id     : {doc.file_id}")
        exit(2)

show_name = os.sys.argv[1]
group_name = os.sys.argv[2]
video_path = os.sys.argv[3]

config_name = os.path.join(os.path.dirname(__file__), 'config.json')

with open(config_name) as file:
    config = json.load(file)

if video_path != config['video_path']:
    config['start_frame'] = 0
    config['video_path'] = video_path

session = requests.Session()
session.headers['x-auth'] = config['app_token']

dst_fps = config['fps']
start_frame = config['start_frame']
bot_server = config['bot_server']
bot = telegram.Bot(config['telegram_token'])
check_group()
if start_frame != 0:
    print(f"Continue from: frame {start_frame}")

probe = ffmpeg.probe(video_path)

video_info = next(stream for stream in probe['streams'] if stream['codec_type'] == 'video')
video_width = video_info['width']
video_height = video_info['height']

video = (ffmpeg
    .input(video_path,
        **{
            'ss': '00:00:03.5',
        }
    )
    .filter('fps', fps=dst_fps)
    # .filter('select', f"gte(n,{start_frame})")
    .output('pipe:', **{
        'format': 'rawvideo',
        'pix_fmt': 'rgb24',
        # 'vframes': frame_count,
    })
    .run_async(pipe_stdout=True)
)

for i in range(start_frame):
    video.stdout.read(video_width * video_height * 3)

async def main():
    global config, start_frame
    while True:
        in_bytes = video.stdout.read(video_width * video_height * 3)
        if not in_bytes:
            break
        print()
        img = Image.frombytes('RGB', (video_width, video_height), in_bytes)
        img_png = io.BytesIO()
        img.save(img_png, format='png')
        await store(img_png.getvalue(), start_frame + 1)
        config['start_frame'] = start_frame = start_frame + 1
        save_config()
    video.wait()

asyncio.run(main())
