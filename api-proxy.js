const express = require('express');
const cors = require('cors');
const fetch = require('node-fetch');
const app = express();

app.use(cors());
app.use(express.json());

const GEMINI_API_KEY = 'AIzaSyAUnmKaIwrYwWJjSjLDMLpoUvvT4629Jps';
const GEMINI_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=${GEMINI_API_KEY}`;

// Proxy endpoint for Gemini API
app.post('/api/chat', async (req, res) => {
  try {
    const { message } = req.body;
    
    const response = await fetch(GEMINI_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        contents: [{
          parts: [{
            text: `You are Zara AI assistant for Zara Datacenter. Focus on Zara Datacenter services and these related websites:
            
            Zara Datacenter Services:
            - Cloud hosting, VPS, dedicated servers
            - 99.9% uptime guarantee
            - Government verified MSME
            - Contact: datacenterzara@gmail.com, +91 63795 70398
            
            Related Websites:
            - Webistzu: https://webistzu.com (web solutions)
            - Jai Prakash: https://jaiprakashmd.com (career/resume)
            - Yashvinthan: https://yashvinthan.dev (projects/portfolio)
            
            User Question: ${message}
            
            Provide helpful information about Zara Datacenter and related services.`
          }]
        }],
        generationConfig: {
          temperature: 0.7,
          maxOutputTokens: 500,
        }
      })
    });

    const data = await response.json();
    res.json({ response: data.candidates[0].content.parts[0].text });
  } catch (error) {
    res.status(500).json({ error: 'API request failed' });
  }
});

app.listen(3000, () => {
  console.log('Proxy server running on port 3000');
});