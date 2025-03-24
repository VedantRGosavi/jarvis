// Example of using environment variables for OAuth in your application

// Import necessary modules
const express = require('express');
const passport = require('passport');
const GoogleStrategy = require('passport-google-oauth20').Strategy;
const GitHubStrategy = require('passport-github2').Strategy;
// Import other strategies as needed

const app = express();

// Configure Google OAuth Strategy
passport.use(new GoogleStrategy({
    clientID: process.env.GOOGLE_CLIENT_ID,
    clientSecret: process.env.GOOGLE_CLIENT_SECRET,
    callbackURL: process.env.GOOGLE_REDIRECT_URI, // This will automatically resolve to the correct URL based on APP_ENV
  },
  (accessToken, refreshToken, profile, done) => {
    // User authentication logic here
    return done(null, profile);
  }
));

// Configure GitHub OAuth Strategy
passport.use(new GitHubStrategy({
    clientID: process.env.GITHUB_CLIENT_ID,
    clientSecret: process.env.GITHUB_CLIENT_SECRET,
    callbackURL: process.env.GITHUB_REDIRECT_URI, // This will automatically resolve to the correct URL based on APP_ENV
  },
  (accessToken, refreshToken, profile, done) => {
    // User authentication logic here
    return done(null, profile);
  }
));

// Setup routes for Google OAuth
app.get('/auth/google',
  passport.authenticate('google', { scope: ['profile', 'email'] })
);

app.get('/api/auth/callback/google',
  passport.authenticate('google', { failureRedirect: '/login' }),
  (req, res) => {
    // Successful authentication, redirect home
    res.redirect('/');
  }
);

// Setup routes for GitHub OAuth
app.get('/auth/github',
  passport.authenticate('github', { scope: ['user:email'] })
);

app.get('/api/auth/callback/github',
  passport.authenticate('github', { failureRedirect: '/login' }),
  (req, res) => {
    // Successful authentication, redirect home
    res.redirect('/');
  }
);

// Environment checking for debugging
console.log('Current environment:', process.env.APP_ENV);
console.log('Using Google redirect URI:', process.env.GOOGLE_REDIRECT_URI);
console.log('Using GitHub redirect URI:', process.env.GITHUB_REDIRECT_URI);

// Start server
const PORT = process.env.PORT || 8000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
