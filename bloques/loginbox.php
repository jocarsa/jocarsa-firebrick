<div class="landing-container">
        <!-- LOGIN BOX -->
        <div id="login-box">
            <h2>Login</h2>
            <form method="post">
                <input type="hidden" name="login_form" value="1">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Login</button>
            </form>
            <?php if ($error && isset($_POST['login_form'])): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <a class="toggle-link" onclick="showSignup()">Sign Up as Admin</a>
        </div>

        <!-- SIGNUP BOX (admins only) -->
        <div id="signup-box" style="display:none;">
            <h2>Sign Up (Admin)</h2>
            <form method="post">
                <input type="hidden" name="signup_form" value="1">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Sign Up</button>
            </form>
            <?php if ($error && isset($_POST['signup_form'])): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <a class="toggle-link" onclick="showLogin()">Back to Login</a>
        </div>
    </div>
