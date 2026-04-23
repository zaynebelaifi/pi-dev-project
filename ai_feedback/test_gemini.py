import os

from dotenv import load_dotenv
from google import genai

load_dotenv()

api_key = os.getenv("GOOGLE_API_KEY") or ""
print(f"DEBUG: API Key found: {api_key[:10]}...")

client = genai.Client()

try:
    response = client.models.generate_content(
        model="models/gemini-2.5-flash",
        contents="Hello, are you there?",
    )
    print("CONNECTION SUCCESS!")
    print(f"Gemini says: {getattr(response, 'text', '')}")
except Exception as exc:
    print("CONNECTION FAILED!")
    print(f"Error: {exc}")
