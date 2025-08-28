# Project Setup

<br>

## Step 1 – Create Configuration File

Created a `config.local.php` file to store **Brevo** API credentials and OTP policy settings.  
This file loads environment variables, sets OTP limits, handles logging, and provides helper functions for CORS and JSON responses.

<br>

## Step 2 – Initialize Frontend

Created a standalone React app using:

```bash
npx create-react-app client
cd client
```

## Step 3 – Install UI Dependencies

Installed Bootstrap for styling and React-Toastify for toast notifications:

```bash
npm install bootstrap@5.3.8
npm install react-toastify
```

## Step 4 – Create Login Page

Built a React login page that uses Bootstrap for styling and React-Toastify for notifications.
The page lets users enter a username and email, request an OTP from the backend, verify it, store the token in localStorage, and redirect to /index.php upon success.

<br>
