<!DOCTYPE html>
<html>
<head>
  <title>Register Page</title>

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

      margin: 80px auto;   /* center box */
    }

    h1 {
      text-align: center;
      color: purple;
    }
    
    textarea {

 width: 100%;
  padding: 10px;
  margin: 10px 0;

  border: 1px solid #ccc;
  border-radius: 5px;

  resize: none;  /* prevents dragging */
}

select {
  width: 100%;
  padding: 10px;
  margin: 10px 0;

  border: 2px solid purple;   /* purple border */
  border-radius: 5px;

  background-color: white;                         
  color: purple                   
  font-size: 14px;
}

/* when user clicks dropdown */
select:focus {
  outline: none;
  border-color: darkviolet;
  background-color:white;
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

    .login-link {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }

    .login-link a {
      color: purple;
      text-decoration: none;
      font-weight: bold;
    }
  </style>
</head>

<body>

  <div class="container">
    <h1>Register</h1>

      <form name="registerForm" method="POST" action="#" onsubmit="return validateForm()">

      <input type="text" name="first_name" placeholder="First Name" required>

      <input type="text" name="last_name" placeholder="Last Name" required>

      <input type="date" name="dob" required>

      <select name="gender" required>
  <option value="">Select Gender</option>
  <option value="Male">Male</option>
  <option value="Female">Female</option>
</select>

  
      <textarea name="address" placeholder="Address" rows="3" required></textarea> 
            
      <input type="text" name="phone" placeholder="Phone Number" required>
   
      <input type="text" name="alt_phone" placeholder="Alternate Number" required>

      <input type="email" name="email" placeholder="Email" required>

      <input type="password" name="password" placeholder="Password" required>

      <input type="password" name="confirm_password" placeholder="Confirm Password"
required>

      <button type="submit">Sign Up</button>
    </form>

    <div class="login-link">
      Already have an account?
      <a href="logins.php">Login</a>
    </div>
  </div>

    <script>
function validateForm() {
    // get values
  var fname = document.forms["registerForm"]["first_name"].value;
 var lname =document.forms["registerForm"]["last_name"].value;
var email = document.forms["registerForm"]["email"].value;
  var phone = document.forms["registerForm"]["phone"].value;
    var gender = document.forms["registerForm"]["gender"].value;
  var password = document.forms["registerForm"]["password"].value;
  var confirm = document.forms["registerForm"]["confirm_password"].value;

  // name check
  if (fname ==""|| lname == ""){
    alert("First and Last name must be filled out");
    return false;
  }

  // email check
  if (email.indexOf("@") == -1 || email.indexOf(".") == -1) {
    alert("Please enter a valid email");
    return false;
  }

  // phone check
  if (isNaN(phone) || phone.length != 10) {
    alert("Phone number must be 10 digits");
    return false;
  }

  // gender check
  if (gender == "") {
    alert("Please select gender");
    return false;
  }

  // password length check
  if (password.length < 6) {
    alert("Password must be at least 6 characters");
    return false;
  }

  // password match check
  if (password != confirm) {
    alert("Passwords do not match");
    return false;
  }

  // if all correct
  return true;
}
</script>

</body>
</html>
