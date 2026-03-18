<!DOCTYPE html>
<html>
<head>
  <title>Login Page</title>

  <style>
    body {
      background-color: lavender;
      font-family: Arial, sans-serif;
    }

    .container {
      width: 400px;
      padding: 30px;

      background-color: white;
      border: 2px solid purple;
      border-radius: 10px;

      margin: 100px auto;   /* center login box */
    }

    h1 {
      text-align: center;
      color: purple;
    }

    input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;

      border: 1px solid #ccc;
      border-radius: 5px;
    }

    button {
      width: 100%;
      padding: 10px;

      background-color: purple;
      color: white;

      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    button:hover {
      background-color: darkviolet;
    }

    .forgot {
      text-align: right;
      margin-top: 10px;
    }

    .forgot a {
      text-decoration: none;
      color: purple;
      font-size: 14px;
    }

    .signup {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }

    .signup a {
      color: purple;
      text-decoration: none;
      font-weight: bold;
    }
  </style>
</head>

<body>

  <div class="container">
    <h1>Login</h1>

    <form method="POST" action="#">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>

      <button type="submit">Login</button>
    </form>

    <div class="forgot">
      <a href="#">Forgot Password?</a>
    </div>

    <div class="signup">
      New user? <a href="register.php">Sign up</a>
    </div>
  </div>

</body>
</html>
