
<?php
$host = "localhost";
$dbname = "library";
$username = "root";
$password = "";

$message = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $name = $_POST["name"];
        $email = $_POST["email"];
        $phone = $_POST["phone"];
         $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO members (name, email, phone, password)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([$name, $email, $phone, $password]);

         echo "<script>alert('member register success');</script>";
         exit();
    }

} catch(PDOException $e){
    $message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Member Registration</title>

    <style>
        body{
            font-family: Arial; 
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container{
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
            width: 400px;
        }

        h2{
            text-align: center;
        }

        input{
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"]{
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover{
            background: #0056b3;
        }

        .message{
            text-align: center;
            color: green;
        }

        /* Responsive */
        @media(max-width: 500px){
            .container{
                width: 90%;
            }
        }
    </style>

    <script>
        function validateForm(){
            let phone = document.forms["memberForm"]["phone"].value;
            let password = document.forms["memberForm"]["password"].value;

            if(phone.length < 10){
                alert("Phone number must be at least 10 digits");
                return false;
            }

            if(password.length < 4){
                alert("Password must be at least 4 characters");
                return false;
            }

            return true;
        }
    </script>

</head>
<body>

<div class="container">
    <h2>Member Registration</h2>

    <div class="message">
        <?php echo $message; ?>
    </div>

    <form name="memberForm" action="members.php" method="POST" onsubmit="return validateForm()">
        <label>Full Name</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Phone</label>
        <input type="text" name="phone" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <input type="submit" value="Register">
    </form>
</div>

</body>
</html>