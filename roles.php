<?php
function allow_roles(array $roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
        // Output a stylish Access Denied page
        echo '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Denied</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    background: #0f0c29;
                    background: linear-gradient(135deg, #24243e, #302b63, #0f0c29);
                    font-family: "Segoe UI", Roboto, sans-serif;
                    color: white;
                }

                .error-card {
                    background: rgba(255, 255, 255, 0.05);
                    backdrop-filter: blur(15px);
                    -webkit-backdrop-filter: blur(15px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    padding: 3rem;
                    border-radius: 24px;
                    text-align: center;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
                    max-width: 450px;
                    width: 90%;
                    animation: shake 0.5s ease-in-out;
                }

                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-10px); }
                    75% { transform: translateX(10px); }
                }

                .icon {
                    font-size: 80px;
                    margin-bottom: 20px;
                    display: block;
                }

                h1 {
                    font-size: 2rem;
                    margin-bottom: 10px;
                    color: #ff4757;
                }

                p {
                    color: #ced6e0;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }

                .btn-home {
                    display: inline-block;
                    padding: 12px 35px;
                    background: #6c5ce7;
                    color: white;
                    text-decoration: none;
                    border-radius: 50px;
                    font-weight: bold;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
                }

                .btn-home:hover {
                    background: #a29bfe;
                    transform: translateY(-3px);
                    box-shadow: 0 6px 20px rgba(108, 92, 231, 0.6);
                }
            </style>
        </head>
        <body>
            <div class="error-card">
                <span class="icon">🚫</span>
                <h1>Access Denied</h1>
                <p>Sorry, you do not have the required permissions to view this restricted page.</p>
                <a href="index.php" class="btn-home">Back to Home</a>
            </div>
        </body>
        </html>';
        exit; // Important: Stop further script execution
    }
}
?>