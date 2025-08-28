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

## Step 5 – Database Schema Updates

The database structure was updated to support OTP authentication, API tokens, and better tracking of user data.

### Table: `config`

- No structural changes.
- Minor charset/collation updates.

### Table: `contacts`

- **New fields:**
  - `created_at` → tracks when the contact was added.
  - `updated_at` → auto-updates whenever the contact row changes.

### Table: `messages`

- **New field:**
  - `id` (bigint unsigned) → unique reference for messages.
- **New index:**
  - `idx_messages_user_time` on `(belongs_to_username, msg_datetime)` for faster queries.

### Table: `users`

- **New fields for authentication & session management:**
  - `email`
  - `otp_hash`
  - `otp_expires_at`
  - `otp_last_sent_at`
  - `otp_hourly_count`, `otp_hourly_reset_at`
  - `otp_daily_count`, `otp_daily_reset_at`
  - `otp_attempts`
  - `api_token`
  - `api_token_expires_at`
  - `last_login_at`

These additions enable OTP verification, rate limiting, token-based authentication, and last login tracking.

<br>
