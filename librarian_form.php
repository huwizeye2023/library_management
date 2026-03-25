<?php
$host = "localhost";
$dbname = "library_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $names = $_POST["names"];
    $telephone = $_POST["telephone"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO librarians (names,email,telephone,password)
         VALUES (?, ?, ?, ?)"
    );

    if($stmt->execute([$names, $email, $telephone, $password])){
        $message = "Librarian registered successfully!";
    } else {
        $message = "Error registering librarian!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Librarian Registration</title>
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

        @media(max-width: 500px){
            .container{
                width: 90%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Librarian Registration</h2>

    <?php if(isset($message)) echo "<p class='message'>$message</p>"; ?>

    <form method="POST">
        

        <label>name</label>
        <input type="names" name="names" required>

        <label>Telephone</label>
        <input type="telephone" name="telephone" required>

        <label>email</label>
        <input type="email" name="email" required>

<div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6" placeholder="Min 6 characters"></div>

    </form>
</div>

</body>
</html>