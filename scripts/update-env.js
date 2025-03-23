#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Read the example env file
const exampleEnvPath = path.join(__dirname, '..', '.env.example');
const envExample = fs.readFileSync(exampleEnvPath, 'utf8');

// Parse the example env file to get variable names
const variables = envExample.split('\n')
  .filter(line => line.trim() && !line.startsWith('#'))
  .map(line => {
    const [key] = line.split('=');
    return key.trim();
  });

// Generate Vercel CLI commands to set environment variables
const commands = variables.map(variable => {
  return `vercel env add ${variable}`;
});

// Print instructions
console.log('Run the following commands to set up your Vercel environment variables:');
console.log('\n');
commands.forEach(cmd => console.log(cmd));
console.log('\n');
console.log('After setting all variables, run this to deploy the variables:');
console.log('vercel env pull .env.production');
console.log('vercel --prod'); 