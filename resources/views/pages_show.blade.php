<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facebook and Instagram Pages Showcase</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold text-center mb-6">Facebook and Instagram Pages Showcase</h1>

    <!-- Dropdown to Select Page -->
    <div class="mb-4">
      <label for="page-select" class="block text-lg font-semibold mb-2">Select a Facebook Page:</label>
      <select id="page-select" class="w-full p-3 border rounded-lg">
        <option value="511524108707522">BizTorg</option>
      </select>
    </div>

    <!-- Buttons to Fetch Data -->
    <div class="flex space-x-4 mb-6">
      <button id="fetch-btn" class="w-full bg-red-500 text-white p-3 rounded-lg font-semibold hover:bg-red-600">
        Fetch Page Details
      </button>
      <button id="engagement-btn" class="w-full bg-green-500 text-white p-3 rounded-lg font-semibold hover:bg-green-600">
        Fetch Page Engagement
      </button>
    </div>

    <!-- Table to Display Page Details -->
    <div id="page-data" class="mt-6 hidden">
      <h2 class="text-xl font-bold mb-4">Facebook Page Details:</h2>
      <table class="w-full border-collapse border border-gray-300 bg-white rounded-lg">
        <thead>
          <tr>
            <th class="border border-gray-300 p-2 text-left">Field</th>
            <th class="border border-gray-300 p-2 text-left">Value</th>
          </tr>
        </thead>
        <tbody id="page-table-body"></tbody>
      </table>
    </div>

    <!-- Table to Display Engagement Info -->
    <div id="engagement-data" class="mt-6 hidden">
      <h2 class="text-xl font-bold mb-4">Facebook Page Engagement:</h2>
      <table class="w-full border-collapse border border-gray-300 bg-white rounded-lg">
        <thead>
          <tr>
            <th class="border border-gray-300 p-2 text-left">Metric</th>
            <th class="border border-gray-300 p-2 text-left">Value</th>
          </tr>
        </thead>
        <tbody id="engagement-table-body"></tbody>
      </table>
    </div>

    <!-- Instagram Section -->
    <div style="margin-top: 90px">
      <label for="insta-select" class="block text-lg font-semibold mb-2">Select Instagram Account:</label>
      <select id="insta-select" class="w-full p-3 border rounded-lg">
        <option value="17841468384967861">BizTorg</option>
      </select>
      <button id="insta-btn" class="w-full bg-red-500 text-white p-3 rounded-lg font-semibold mt-4 hover:bg-red-600">
        Fetch Instagram Details
      </button>

      <div id="insta-data" class="mt-6 hidden">
        <h2 class="text-xl font-bold mb-4">Instagram Details:</h2>
        <div class="instagram-account bg-white p-4 rounded-lg shadow">
          <img id="insta-profile-picture" class="w-20 h-20 rounded-full mb-4" src="" alt="Profile Picture" />
          <h2 id="insta-username" class="text-lg font-semibold"></h2>
          <p><strong>Media Count:</strong> <span id="insta-media-count"></span> posts</p>
          <p><strong>Followers:</strong> <span id="insta-followers"></span></p>
          <p><strong>Following:</strong> <span id="insta-following"></span></p>
        </div>
      </div>

      <!-- Instagram Posts Section -->
      <button id="insta-posts-btn" class="w-full bg-green-500 text-white p-3 rounded-lg font-semibold mt-4 hover:bg-green-600">
        Fetch Instagram Posts
      </button>

      <div id="insta-posts" class="mt-6 hidden">
        <h2 class="text-xl font-bold mb-4">Instagram Posts:</h2>
        <div id="insta-posts-list" class="space-y-4"></div>
      </div>
    </div>
  </div>

  <script>
    const ACCESS_TOKEN = 'EAANaazjLaZCkBOZCTa2ZAkGA83XuMWgZB71z8TZCBV7QQ8X3lHaqynKtfRZC68S1zO3HvL3NXM2O3xvHbdnh8JMz2nzsW82jM5KQTADJfqd4KHxR5u4wQwvHlwWQ38ROAuMuCXbLIbLaOaacLet5ZCRobGe1jqYHZBfoY8clZAcE8m6uym8MOZA6QEuXqJ';

    // Facebook Page Details Fetching
    document.getElementById('fetch-btn').addEventListener('click', async () => {
      const pageId = document.getElementById('page-select').value;

      try {
        const response = await fetch(`https://graph.facebook.com/v17.0/${pageId}?fields=id,name,about,category,fan_count,website,phone,emails&access_token=${ACCESS_TOKEN}`);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const pageData = await response.json();
        const tableBody = document.getElementById('page-table-body');
        tableBody.innerHTML = `
          <tr><td class="border border-gray-300 p-2">ID</td><td class="border border-gray-300 p-2">${pageData.id}</td></tr>
          <tr><td class="border border-gray-300 p-2">Name</td><td class="border border-gray-300 p-2">${pageData.name}</td></tr>
          <tr><td class="border border-gray-300 p-2">About</td><td class="border border-gray-300 p-2">${pageData.about || 'N/A'}</td></tr>
          <tr><td class="border border-gray-300 p-2">Category</td><td class="border border-gray-300 p-2">${pageData.category}</td></tr>
          <tr><td class="border border-gray-300 p-2">Fan Count</td><td class="border border-gray-300 p-2">${pageData.fan_count || 'N/A'}</td></tr>
          <tr><td class="border border-gray-300 p-2">Website</td><td class="border border-gray-300 p-2">${pageData.website || 'N/A'}</td></tr>
          <tr><td class="border border-gray-300 p-2">Phone</td><td class="border border-gray-300 p-2">${pageData.phone || 'N/A'}</td></tr>
          <tr><td class="border border-gray-300 p-2">Emails</td><td class="border border-gray-300 p-2">${(pageData.emails || []).join(', ') || 'N/A'}</td></tr>
        `;

        document.getElementById('page-data').classList.remove('hidden');
      } catch (error) {
        console.error('Error fetching Facebook page details:', error);
        alert('Failed to fetch Facebook page details. Please check your access token.');
      }
    });

    // Facebook Engagement Fetching
    document.getElementById('engagement-btn').addEventListener('click', async () => {
  const pageId = document.getElementById('page-select').value;
  const PAGE_TOKEN = 'EAANaazjLaZCkBOZCWEatHpYio5jkCNky0pMYSJYH0DFXvwl0cXjkQZBOwXW6WLcMtQD2A33H8EpNryMLzFXyxXElbWP1X57UGCi0t6MfGi6bB7KgKWikxZB26udZAJicDFZBHsiBiBZBp79qAuOMV7U5qyeBh85AlWZBK8OT6WzYqRgYKfzr4euSGssZApvCQNxaW';

  try {
    const response = await fetch(
      `https://graph.facebook.com/v17.0/${pageId}/feed?fields=id,message,created_time,likes.summary(true),comments.summary(true),shares,attachments,permalink_url,reactions.summary(true)&access_token=${PAGE_TOKEN}`
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const feedData = await response.json();
    const tableBody = document.getElementById('engagement-table-body');
    tableBody.innerHTML = '';

    feedData.data.forEach(post => {
      tableBody.innerHTML += `
        <tr><td class="border border-gray-300 p-2">Post</td><td class="border border-gray-300 p-2">${post.message || 'No Message'}</td></tr>
        <tr><td class="border border-gray-300 p-2">Created Time</td><td class="border border-gray-300 p-2">${new Date(post.created_time).toLocaleString()}</td></tr>
        <tr><td class="border border-gray-300 p-2">Likes</td><td class="border border-gray-300 p-2">${post.likes?.summary?.total_count || 0}</td></tr>
        <tr><td class="border border-gray-300 p-2">Comments</td><td class="border border-gray-300 p-2">${post.comments?.summary?.total_count || 0}</td></tr>
        <tr><td class="border border-gray-300 p-2">Shares</td><td class="border border-gray-300 p-2">${post.shares?.count || 0}</td></tr>
        <tr><td class="border border-gray-300 p-2">Post Link</td><td class="border border-gray-300 p-2"><a href="${post.permalink_url}" target="_blank">View Post</a></td></tr>
      `;
    });

    document.getElementById('engagement-data').classList.remove('hidden');
  } catch (error) {
    console.error('Error fetching Facebook engagement:', error);
    alert('Failed to fetch Facebook engagement. Please check your access token.');
  }
});


    // Instagram Details Fetching
    document.getElementById('insta-btn').addEventListener('click', async () => {
      const instaId = document.getElementById('insta-select').value;

      try {
        const response = await fetch(`https://graph.facebook.com/v17.0/${instaId}?fields=username,media_count,followers_count,follows_count,profile_picture_url&access_token=${ACCESS_TOKEN}`);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const instaData = await response.json();

        document.getElementById('insta-profile-picture').src = instaData.profile_picture_url;
        document.getElementById('insta-username').textContent = `Instagram Account: @${instaData.username}`;
        document.getElementById('insta-media-count').textContent = instaData.media_count;
        document.getElementById('insta-followers').textContent = instaData.followers_count;
        document.getElementById('insta-following').textContent = instaData.follows_count;

        document.getElementById('insta-data').classList.remove('hidden');
      } catch (error) {
        console.error('Error fetching Instagram details:', error);
        alert('Failed to fetch Instagram details. Please check your access token.');
      }
    });

    // Instagram Posts Fetching
    document.getElementById('insta-posts-btn').addEventListener('click', async () => {
      const instaId = document.getElementById('insta-select').value;

      try {
        const response = await fetch(`https://graph.facebook.com/v17.0/${instaId}/media?fields=id,media_type,media_url,caption,timestamp&access_token=${ACCESS_TOKEN}`);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const postsData = await response.json();
        const postsList = document.getElementById('insta-posts-list');
        postsList.innerHTML = '';

        postsData.data.forEach(post => {
          const postElement = document.createElement('div');
          postElement.classList.add('bg-white', 'p-4', 'rounded-lg', 'shadow');
          postElement.innerHTML = `
            <p><strong>Caption:</strong> ${post.caption || 'No Caption'}</p>
            <p><strong>Posted On:</strong> ${new Date(post.timestamp).toLocaleString()}</p>
            <img class="w-full mt-2" src="${post.media_url}" alt="Instagram Post Image" />
          `;
          postsList.appendChild(postElement);
        });

        document.getElementById('insta-posts').classList.remove('hidden');
      } catch (error) {
        console.error('Error fetching Instagram posts:', error);
        alert('Failed to fetch Instagram posts. Please check your access token.');
      }
    });
  </script>
</body>
</html>
