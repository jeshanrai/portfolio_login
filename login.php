<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'portfolio');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$login_error = '';
$register_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            header("Location: index.php");
            exit;
        } else {
            $login_error = "Invalid email or password.";
        }

        $stmt->close();
    }

    if ($_POST["form_type"] === "register") {
        $name = $_POST["name"];
        $email = $_POST["email"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $address = $_POST["address"];
        $phone = $_POST["phone"];
        $created_at = $_POST["created_at"];

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, address, phone, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $password, $address, $phone, $created_at);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit;
        } else {
            $register_error = "Registration failed. Email may already exist.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Smooth Div Slide</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #c3cfe2, #c3d9ff);
    }
    .action-btn {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      background: #6c8dff;
      color: white;
      cursor: pointer;
      font-size: 16px;
      margin-bottom: 15px;
      transition: background 0.3s ease;
    }

    .action-btn:hover {
      background: #5274f3;
    }
   
    .container {
        width: 900px;
      height: 500px;
      display: flex;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
      backdrop-filter: blur(15px);
      overflow: hidden;
      position: relative;
      transition: all 0.6s ease-in-out;
    }
    .social-text {
      font-size: 13px;
      color: #666;
      margin: 15px 0;
    }
    .form-box {
      width: 100%;
      max-width: 320px;
      text-align: center;
      transition: all 0.6s ease;
    }

    .form-box h2 {
      color: #333;
      font-size: 22px;
      margin-bottom: 20px;
    }

    .input-group {
      position: relative;
      margin-bottom: 15px;
    }

    .input-group input {
      width: 100%;
      padding: 12px 40px 12px 15px;
      border: none;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.9);
      outline: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .input-group .icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: #777;
    }
  
    .social-icons {
      display: flex;
      justify-content: center;
      gap: 15px;
    }

    .social-icons img {
      width: 28px;
      height: 28px;
      transition: transform 0.3s ease;
    }

    .social-icons img:hover {
      transform: scale(1.1);
    }


    .slider {
      display: flex;
      width: 2700px;
      transition: transform 0.6s ease;
    }

    .box {
      width: 450px;
      height: 100%;
      padding: 20px;
      text-align: center;
      font-size: 24px;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }

    .left {
        background: rgba(255, 255, 255, 0.2);
    }
    .middle {
 
  background: rgba(255, 255, 255, 0.2);
  padding: 0px;

  
}


.panel-section h2 {
      font-size: 26px;
      margin-bottom: 10px;
    }
    .panel-section {
  border-radius: 150px 0px 0px 150px;
  background-color: #7c9eff;
  width: 450px;
  height: 100%;
  padding-top: 157.5px;
  transition: border-radius 0.6s ease, padding-top 0.6s ease;
}

   
    .panel-section.slide-active {
  border-radius: 0px 150px 150px 0px;
  padding-top: 185px;
}


    .panel-section p {
      font-size: 14px;
      margin-bottom: 20px;
      opacity: 0.9;
    }

    .panel-section button {
      padding: 10px 30px;
      border: 1px solid white;
      background: transparent;
      border-radius: 8px;
      color: white;
      cursor: pointer;
      transition: 0.3s;
    }

    .panel-section button:hover {
      background: white;
      color: #7c9eff;
    }

    .right {
        background: rgba(255, 255, 255, 0.2);
    }

    button {
      margin-top: 20px;
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      background-color: #333;
      color: #fff;
      border-radius: 5px;
      cursor: pointer;
    }

    button:hover {
      background-color: #555;
    }
    #slider {
  transition: transform 0.5s ease-in-out;
}
.background_div{
background: rgba(255, 255, 255, 0.2);
  width: auto;
}

#slider {
  transition: transform 0.8s cubic-bezier(0.77, 0, 0.175, 1); /* smoother easing */
}

.error {
  color: red;
  font-size: 14px;
  margin-top: 5px;
}

    
  </style>
</head>
<body>
 
  <div class="container">
    <div class="slider" id="slider">
      <div class="box left"><div class="form-section">
        <div class="form-box" id="loginBox">
          <h2>Login</h2>
          <form method="POST">
        <input type="hidden" name="form_type" value="login">
        <div class="input-group">
          <input type="email" name="email" placeholder="Email" required>
          <span class="icon">📧</span>
        </div>
        <div class="input-group">
          <input type="password" name="password" placeholder="Password" required>
          <span class="icon">🔒</span>
        </div>
    
          <button class="action-btn">Login</button>
          <?php if (!empty($login_error)): ?>
    <p class="error"><?php echo htmlspecialchars($login_error); ?></p>
  <?php endif; ?>
          </form>
          <p class="social-text">or login with social platforms</p>
          <div class="social-icons">
            <a href="#"><img src="https://img.icons8.com/ios-filled/50/000000/google-logo.png" alt="Google" /></a>
            <a href="#"><img src="https://img.icons8.com/ios-filled/50/000000/facebook-new.png" alt="Facebook" /></a>
            <a href="#"><img src="https://img.icons8.com/ios-filled/50/000000/github.png" alt="GitHub" /></a>
            <a href="#"><img src="https://img.icons8.com/ios-filled/50/000000/linkedin.png" alt="LinkedIn" /></a>
          </div>
          
        </div>
        
        
      </div></div>
      <div class="box middle">
        <div class="panel-section" id="panelSection">
          <h2 id="panelTitle">Hello, Welcome!</h2>
          <p id="panelDesc">Don't have an account?</p>
          
        <button id="slideBtn" onclick="toggleSlide()">Register</button>
      </div>
      </div>
      <div class="box right">  <div class="form-box">
        <h2>Register</h2>
        <form method="POST">
  <input type="hidden" name="form_type" value="register">
  <input type="hidden" name="created_at" value="<?php echo date('Y-m-d H:i:s'); ?>">

  <div class="input-group">
    <input type="text" name="name" placeholder="Full Name" required>
    <span class="icon">👤</span>
  </div>

  <div class="input-group">
    <input type="email" name="email" placeholder="Email" required>
    <span class="icon">📧</span>
  </div>

  <div class="input-group">
    <input type="password" name="password" placeholder="Password" required>
    <span class="icon">🔒</span>
  </div>

  <div class="input-group">
    <input type="text" name="address" placeholder="Address" required>
    <span class="icon">🏠</span>
  </div>

  <div class="input-group">
    <input type="tel" name="phone" placeholder="Phone Number" required pattern="[0-9]{7,15}">
    <span class="icon">📞</span>
  </div>

  <button class="action-btn" type="submit">Register</button>
  <?php if (!empty($register_error)) echo "<p class='error'>$register_error</p>"; ?>

  <p class="social-text">or register with social platforms</p>
  <div class="social-icons">
    <a href="#"><img src="https://img.icons8.com/ios-filled/50/000000/google-logo.png" alt="Google" /></a>
    <a href="#"><img src="https://img.icons8.com/ios-filled/50/000000/facebook-new.png" alt="Facebook" /></a>
    <a href="#"><img src="https://img.icons8.com/ios-filled/50/000000/github.png" alt="GitHub" /></a>
    <a href="#"><img src="https://img.icons8.com/ios-filled/50/000000/linkedin.png" alt="LinkedIn" /></a>
  </div>
</form>

    </div>
  </div>
  

  <script>
    let isSlidLeft = false;

function toggleSlide() {
  const slider = document.getElementById("slider");
  const button = document.getElementById("slideBtn");
  const desc = document.getElementById("panelDesc");

  const middleBox = document.querySelector(".panel-section"); // ✅ get the .middle div

  if (!isSlidLeft) {
    slider.style.transform = "translateX(-451px)";
    button.textContent = "Login";
    desc.textContent = "Already have an account?";
    isSlidLeft = true;
  } else {
    slider.style.transform = "translateX(0px)";
    button.textContent = "Register";
    desc.textContent = "Don't have an account?";
    isSlidLeft = false;
  }

  // ✅ toggle border-radius class
  middleBox.classList.toggle("slide-active");

}


  </script>
</body>
</html>
