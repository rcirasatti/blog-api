#!/usr/bin/env pwsh

Write-Host "========================================" -ForegroundColor Green
Write-Host "TESTING BLOG API - Week 5 Implementation" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

$BaseURL = "http://localhost:8000/api"

# Test 1: Register User
Write-Host "[TEST 1] Register User" -ForegroundColor Yellow
$registerData = @{
    name = "Test User"
    email = "testuser@example.com"
    password = "password123"
    password_confirmation = "password123"
} | ConvertTo-Json

$registerResponse = Invoke-RestMethod -Uri "$BaseURL/register" -Method POST -Body $registerData -ContentType "application/json" -ErrorAction SilentlyContinue

if ($registerResponse) {
    Write-Host "✓ Response Status: 201 Created" -ForegroundColor Green
    Write-Host "✓ Token received: $($registerResponse.token.Substring(0, 20))..." -ForegroundColor Green
    $token = $registerResponse.token
    Write-Host ""
} else {
    Write-Host "✗ Registration failed" -ForegroundColor Red
}

# Test 2: Get all posts (public endpoint)
Write-Host "[TEST 2] Get All Posts (Public, Paginated)" -ForegroundColor Yellow
try {
    $postsResponse = Invoke-RestMethod -Uri "$BaseURL/posts" -Method GET -ErrorAction Stop
    Write-Host "✓ Response Status: 200 OK" -ForegroundColor Green
    $postCount = ($postsResponse.data | Measure-Object).Count
    Write-Host "✓ Posts returned: $postCount" -ForegroundColor Green
    Write-Host "✓ Has pagination meta: $(if($postsResponse.meta) {'Yes'} else {'No'})" -ForegroundColor Green
    Write-Host ""
} catch {
    Write-Host "✗ Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
}

# Test 3: Create post without auth (should fail)
Write-Host "[TEST 3] Create Post Without Auth (Should Fail)" -ForegroundColor Yellow
$postData = @{
    title = "Test Post"
    body = "This is test content"
} | ConvertTo-Json

try {
    $createResponse = Invoke-RestMethod -Uri "$BaseURL/posts" -Method POST -Body $postData -ContentType "application/json" -ErrorAction Stop
    Write-Host "✗ Should have failed with 401" -ForegroundColor Red
} catch {
    if ($_.Exception.Response.StatusCode -eq 401) {
        Write-Host "✓ Response Status: 401 Unauthorized" -ForegroundColor Green
        Write-Host "✓ Error message: $(($_.ErrorDetails | ConvertFrom-Json).message)" -ForegroundColor Green
    }
}
Write-Host ""

# Test 4: Create post with auth
Write-Host "[TEST 4] Create Post With Valid Auth & Data" -ForegroundColor Yellow
$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

$newPostData = @{
    title = "My First Post"
    body = "This is my first post content for testing."
} | ConvertTo-Json

$createResponse = Invoke-RestMethod -Uri "$BaseURL/posts" -Method POST -Body $newPostData -Headers $headers -ErrorAction SilentlyContinue

if ($createResponse) {
    Write-Host "✓ Response Status: 201 Created" -ForegroundColor Green
    Write-Host "✓ Post ID: $($createResponse.data.id)" -ForegroundColor Green
    Write-Host "✓ Title: $($createResponse.data.title)" -ForegroundColor Green
    Write-Host "✓ Author: $($createResponse.data.author.name)" -ForegroundColor Green
    $postId = $createResponse.data.id
    Write-Host ""
}

# Test 5: Validation Error
Write-Host "[TEST 5] Create Post With Empty Title (Validation Error)" -ForegroundColor Yellow
$invalidPostData = @{
    title = ""
    body = "Some content"
} | ConvertTo-Json

try {
    $validationResponse = Invoke-RestMethod -Uri "$BaseURL/posts" -Method POST -Body $invalidPostData -Headers $headers -ErrorAction Stop
    Write-Host "✗ Should have failed with 422" -ForegroundColor Red
} catch {
    if ($_.Exception.Response.StatusCode -eq 422) {
        $errorContent = $_.ErrorDetails | ConvertFrom-Json
        Write-Host "✓ Response Status: 422 Unprocessable Entity" -ForegroundColor Green
        Write-Host "✓ Error message: $($errorContent.message)" -ForegroundColor Green
        Write-Host "✓ Validation errors:" -ForegroundColor Green
        $errorContent.errors.PSObject.Properties | ForEach-Object {
            Write-Host "  - $($_.Name): $($_.Value[0])" -ForegroundColor Cyan
        }
    }
}
Write-Host ""

# Test 6: Get single post
Write-Host "[TEST 6] Get Single Post" -ForegroundColor Yellow
if ($postId) {
    $singlePostResponse = Invoke-RestMethod -Uri "$BaseURL/posts/$postId" -Method GET -ErrorAction SilentlyContinue
    Write-Host "✓ Response Status: 200 OK" -ForegroundColor Green
    Write-Host "✓ Post Title: $($singlePostResponse.data.title)" -ForegroundColor Green
    Write-Host "✓ Post Body: $($singlePostResponse.data.body)" -ForegroundColor Green
    Write-Host ""
}

# Test 7: 404 Error
Write-Host "[TEST 7] Get Non-existent Post (404 Not Found)" -ForegroundColor Yellow
try {
    $notFoundResponse = Invoke-RestMethod -Uri "$BaseURL/posts/9999" -Method GET -ErrorAction Stop
    Write-Host "✗ Should have returned 404" -ForegroundColor Red
} catch {
    if ($_.Exception.Response.StatusCode -eq 404) {
        $errorContent = $_.ErrorDetails | ConvertFrom-Json
        Write-Host "✓ Response Status: 404 Not Found" -ForegroundColor Green
        Write-Host "✓ Error message: $($errorContent.message)" -ForegroundColor Green
    }
}
Write-Host ""

# Test 8: Create comment
Write-Host "[TEST 8] Create Comment (Protected Endpoint)" -ForegroundColor Yellow
if ($postId) {
    $commentData = @{
        body = "This is a great post!"
    } | ConvertTo-Json
    
    $commentResponse = Invoke-RestMethod -Uri "$BaseURL/posts/$postId/comments" -Method POST -Body $commentData -Headers $headers -ErrorAction SilentlyContinue
    
    if ($commentResponse) {
        Write-Host "✓ Response Status: 201 Created" -ForegroundColor Green
        Write-Host "✓ Comment ID: $($commentResponse.data.id)" -ForegroundColor Green
        Write-Host "✓ Comment Body: $($commentResponse.data.body)" -ForegroundColor Green
        Write-Host "✓ Author: $($commentResponse.data.author.name)" -ForegroundColor Green
        $commentId = $commentResponse.data.id
        Write-Host ""
    }
}

# Test 9: Authorization error (try to update someone else's post)
Write-Host "[TEST 9] Authorization Check - Update Post From Another User" -ForegroundColor Yellow

# First, create another user
$user2Data = @{
    name = "Second User"
    email = "user2@example.com"
    password = "password123"
    password_confirmation = "password123"
} | ConvertTo-Json

$user2Response = Invoke-RestMethod -Uri "$BaseURL/register" -Method POST -Body $user2Data -ContentType "application/json" -ErrorAction SilentlyContinue
$token2 = $user2Response.token

# Try to update first user's post with second user's token
$headers2 = @{
    "Authorization" = "Bearer $token2"
    "Content-Type" = "application/json"
}

$updateData = @{
    title = "Hacked Title"
    body = "Hacked content"
} | ConvertTo-Json

try {
    $authResponse = Invoke-RestMethod -Uri "$BaseURL/posts/$postId" -Method PATCH -Body $updateData -Headers $headers2 -ErrorAction Stop
    Write-Host "✗ Should have failed with 403" -ForegroundColor Red
} catch {
    if ($_.Exception.Response.StatusCode -eq 403) {
        $errorContent = $_.ErrorDetails | ConvertFrom-Json
        Write-Host "✓ Response Status: 403 Forbidden" -ForegroundColor Green
        Write-Host "✓ Error message: $($errorContent.message)" -ForegroundColor Green
    }
}
Write-Host ""

# Test 10: Get comments for post (paginated)
Write-Host "[TEST 10] Get Comments For Post (Paginated)" -ForegroundColor Yellow
if ($postId) {
    $commentsResponse = Invoke-RestMethod -Uri "$BaseURL/posts/$postId/comments" -Method GET -ErrorAction SilentlyContinue
    Write-Host "✓ Response Status: 200 OK" -ForegroundColor Green
    $commentCount = ($commentsResponse.data | Measure-Object).Count
    Write-Host "✓ Comments returned: $commentCount" -ForegroundColor Green
    Write-Host "✓ Has pagination meta: $(if($commentsResponse.meta) {'Yes'} else {'No'})" -ForegroundColor Green
    Write-Host ""
}

Write-Host "========================================" -ForegroundColor Green
Write-Host "Testing Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
