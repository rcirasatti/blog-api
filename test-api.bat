@echo off
setlocal enabledelayedexpansion

echo.
echo ========================================
echo  TESTING BLOG API - Week 5
echo ========================================
echo.

set BaseURL=http://localhost:8000/api

echo TEST 1: Register User
curl -s -X POST "%BaseURL%/register" ^
  -H "Content-Type: application/json" ^
  -d "{\"name\":\"TestUser\",\"email\":\"testuser@example.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\"}" > token.json

echo   SUCCESS: Check token.json
echo.

echo TEST 2: Get All Posts (Public)
curl -s -X GET "%BaseURL%/posts" ^
  -H "Content-Type: application/json" > posts.json

echo   SUCCESS: Check posts.json
echo.

echo TEST 3: Create Post Without Auth (Should Fail with 401)
curl -s -w "\n   Status: %%{http_code}\n" -X POST "%BaseURL%/posts" ^
  -H "Content-Type: application/json" ^
  -d "{\"title\":\"Test\",\"body\":\"Content\"}" > error.json

echo.

echo TEST 4: Get Non-existent Post (Should Return 404)
curl -s -w "\n   Status: %%{http_code}\n" -X GET "%BaseURL%/posts/9999" ^
  -H "Content-Type: application/json" > notfound.json

echo.

echo ========================================
echo  Testing Complete!
echo  Files created: token.json, posts.json, error.json, notfound.json
echo ========================================
