<?php
define('a328763fe27bba', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/verify_token.php';
require_auth_or_redirect();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.7/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/plyr/3.7.8/plyr.min.css" />

    <link rel="stylesheet" href="./assets/css/main.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./assets/css/mobile.css?v=<?php echo time(); ?>" />

    <title>היי מה נשמע?</title>

    <script>
    localStorage.setItem("authUser", "<?php echo $GLOBALS['current_user'] ?>")
    </script>

</head>

<body>

    <div id="main" class="main-container row">
        <div id="chats_list" class="left-container col-md-4">
            <!--header -->
            <div class="header row">
                <div class="col-12 row">
                    <div class="user_avatar_container col-2">
                        <img src="./profile_pics/assaf.jpg" alt="User's Avatar" />
                    </div>
                    <div class="user_info_container col-6">
                        <div class="user_full_name_comes_here"><?php echo $GLOBALS['current_user'] ?></div>
                        <div class="user_status_comes_here hide_on_mobile">Online</div>
                    </div>
                    <div class="logout_btn_container col-4">
                        <button class="logout btn btn-dark">Logout</button>
                    </div>
                </div>
            </div>
            <!--search-container -->
            <div class="search-container">
                <div class="input">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search or start new chat   " />
                </div>
                <i class="fa-sharp fa-solid fa-bars-filter"></i>
            </div>
            <!--chats -->
            <div id="chats" class="chat-list"></div>
        </div>

        <div id="chat_window" class="right-container col-md-8">
            <!--header -->
            <div class="header row">

                <div class="row col-10">
                    <div class="show_chats_list col-2">
                        <i class="fa-solid fa-chevron-left"></i>
                    </div>

                    <div class="contact_profile_img col-3">
                        <img class="dp" src="" alt="" />
                    </div>

                    <div class="contact_name_container col-7">
                        <span class="contact_name"></span>
                        <span class="contact_id"></span>
                    </div>
                </div>

                <div class="contact_more_options col-2">
                    <ul class="row">
                        <li class="col-6 show_more_option_menu">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </li>
                    </ul>
                </div>

            </div>
            <!--chat-container -->
            <div id="msgs" class="chat-container"></div>
            <!--input-bottom -->
            <form id="send_msg" class="send_msg_form chatbox-input">
                <!-- File upload button -->
                <button type="button" id="file_btn" class="file-btn">
                    <i class="fa-sharp fa-solid fa-paperclip"></i>
                </button>

                <!-- Hidden file input -->
                <input type="file" id="image_upload" accept="image/*" style="display: none;" />

                <!-- Emoji button -->
                <button type="button" id="emoji_btn" class="emoji-btn">😊</button>

                <!-- Message input -->
                <input id="msg" type="text" placeholder="Type a message" required />

                <!-- Send button -->
                <button type="submit" class="submit_msg">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>


            <!-- Full Emoji Picker -->
            <emoji-picker id="emoji_picker"></emoji-picker>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/plyr/3.7.8/plyr.min.js"></script>

    <!-- emoji picker library -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>


    <script src="./assets/js/main.js"></script>

</body>

</html><?php
define('a328763fe27bba', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/verify_token.php';
require_auth_or_redirect();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.7/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/plyr/3.7.8/plyr.min.css" />

    <link rel="stylesheet" href="./assets/css/main.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./assets/css/mobile.css?v=<?php echo time(); ?>" />

    <title>היי מה נשמע?</title>

    <script>
    localStorage.setItem("authUser", "<?php echo $GLOBALS['current_user'] ?>")
    </script>

</head>

<body>

    <div id="main" class="main-container row">
        <div id="chats_list" class="left-container col-md-4">
            <!--header -->
            <div class="header row">
                <div class="col-12 row">
                    <div class="user_avatar_container col-2">
                        <img src="./profile_pics/assaf.jpg" alt="User's Avatar" />
                    </div>
                    <div class="user_info_container col-6">
                        <div class="user_full_name_comes_here"><?php echo $GLOBALS['current_user'] ?></div>
                        <div class="user_status_comes_here hide_on_mobile">Online</div>
                    </div>
                    <div class="logout_btn_container col-4">
                        <button class="logout btn btn-dark">Logout</button>
                    </div>
                </div>
            </div>
            <!--search-container -->
            <div class="search-container">
                <div class="input">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search or start new chat   " />
                </div>
                <i class="fa-sharp fa-solid fa-bars-filter"></i>
            </div>
            <!--chats -->
            <div id="chats" class="chat-list"></div>
        </div>

        <div id="chat_window" class="right-container col-md-8">
            <!--header -->
            <div class="header row">

                <div class="row col-10">
                    <div class="show_chats_list col-2">
                        <i class="fa-solid fa-chevron-left"></i>
                    </div>

                    <div class="contact_profile_img col-3">
                        <img class="dp" src="" alt="" />
                    </div>

                    <div class="contact_name_container col-7">
                        <span class="contact_name"></span>
                        <span class="contact_id"></span>
                    </div>
                </div>

                <div class="contact_more_options col-2">
                    <ul class="row">
                        <li class="col-6 show_more_option_menu">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </li>
                    </ul>
                </div>

            </div>
            <!--chat-container -->
            <div id="msgs" class="chat-container"></div>
            <!--input-bottom -->
            <form id="send_msg" class="send_msg_form chatbox-input">
                <!-- File upload button -->
                <button type="button" id="file_btn" class="file-btn">
                    <i class="fa-sharp fa-solid fa-paperclip"></i>
                </button>

                <!-- Hidden file input -->
                <input type="file" id="image_upload" accept="image/*" style="display: none;" />

                <!-- Emoji button -->
                <button type="button" id="emoji_btn" class="emoji-btn">😊</button>

                <!-- Message input -->
                <input id="msg" type="text" placeholder="Type a message" required />

                <!-- Send button -->
                <button type="submit" class="submit_msg">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>


            <!-- Full Emoji Picker -->
            <emoji-picker id="emoji_picker"></emoji-picker>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/plyr/3.7.8/plyr.min.js"></script>

    <!-- emoji picker library -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>


    <script src="./assets/js/main.js"></script>

</body>

</html>