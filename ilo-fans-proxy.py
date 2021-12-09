from fastapi import FastAPI
from aiohttp import ClientSession, BasicAuth


# iLO Credentials
ILO_HOST = '192.168.1.69'
ILO_USERNAME = 'your-ilo-username'
ILO_PASSWORD = 'your-ilo-password'

app = FastAPI()


@app.get('/')
async def get_fan_readings():
    async with ClientSession() as client:
        async with client.get(f'https://{ILO_HOST}/redfish/v1/chassis/1/Thermal', ssl=False, auth=BasicAuth(ILO_USERNAME, ILO_PASSWORD)) as resp:
            data = await resp.json()
            return [fan['CurrentReading'] for fan in data['Fans']]
