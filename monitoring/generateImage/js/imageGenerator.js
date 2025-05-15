/**
 * Image Generator Module
 * 
 * Provides functions for generating face images using Stability AI's API
 * and monitoring the generation process.
 */

const API_ENDPOINT = 'https://api.stability.ai/v2beta/stable-image/generate/core';
const IMAGE_WIDTH = 512;
const IMAGE_HEIGHT = 512;

const AGE_DISTRIBUTION = {
  '20': 0.25,  // 25%
  '30': 0.25,  // 25%
  '40': 0.20,  // 20%
  '50': 0.15,  // 15%
  '60': 0.10,  // 10%
  '70': 0.05   // 5%
};

const GENDER_DISTRIBUTION = {
  'male': 0.5,    // 50%
  'female': 0.5   // 50%
};

/**
 * Generate a face image using Stability AI API
 * 
 * @param {Object} params - Generation parameters
 * @param {number} params.age - Age of the person (20, 30, 40, 50, 60, 70)
 * @param {string} params.gender - Gender of the person ('male' or 'female')
 * @param {number} [params.seed] - Optional seed for reproducible generations
 * @returns {Promise<Object>} - Generated image data and metadata
 */
async function generateFaceImage(params) {
  const startTime = Date.now();
  
  try {
    const age = validateAge(params.age);
    const gender = validateGender(params.gender);
    const seed = params.seed || Math.floor(Math.random() * 1000000000);
    
    const prompt = `${age}-year-old ${gender} japanese wearing a suit, photorealistic`;
    
    const response = await callStabilityAPI(prompt, seed);
    
    const imageData = processAPIResponse(response, age, gender, seed);
    
    const endTime = Date.now();
    const responseTime = endTime - startTime;
    
    return {
      ...imageData,
      performance: {
        responseTime,
        timestamp: new Date().toISOString()
      }
    };
  } catch (error) {
    console.error('Error generating face image:', error);
    throw new Error(`Face image generation failed: ${error.message}`);
  }
}

/**
 * Call the Stability AI API to generate an image
 * 
 * @param {string} prompt - Text prompt for image generation
 * @param {number} seed - Random seed for reproducible generations
 * @returns {Promise<Object>} - API response data
 */
async function callStabilityAPI(prompt, seed) {
  const apiKey = process.env.STABILITY_API_KEY;
  if (!apiKey) {
    throw new Error('STABILITY_API_KEY is not set in environment variables');
  }
  
  const requestData = {
    width: IMAGE_WIDTH,
    height: IMAGE_HEIGHT,
    seed: seed,
    cfg_scale: 7.5,
    samples: 1,
    text_prompts: [
      {
        text: prompt,
        weight: 1.0
      }
    ]
  };
  
  let attempts = 0;
  const maxAttempts = 3;
  let lastError;
  
  while (attempts < maxAttempts) {
    try {
      const response = await fetch(API_ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${apiKey}`
        },
        body: JSON.stringify(requestData)
      });
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(`API error: ${response.status} ${response.statusText} - ${JSON.stringify(errorData)}`);
      }
      
      return await response.json();
    } catch (error) {
      lastError = error;
      attempts++;
      
      if (attempts >= maxAttempts) {
        break;
      }
      
      const delay = Math.pow(2, attempts - 1) * 1000;
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
  
  throw new Error(`Failed after ${maxAttempts} attempts: ${lastError.message}`);
}

/**
 * Process the API response and extract image data
 * 
 * @param {Object} response - API response data
 * @param {number} age - Age parameter used for generation
 * @param {string} gender - Gender parameter used for generation
 * @param {number} seed - Seed used for generation
 * @returns {Object} - Processed image data and metadata
 */
function processAPIResponse(response, age, gender, seed) {
  if (!response.artifacts || response.artifacts.length === 0) {
    throw new Error('No image data in API response');
  }
  
  const artifact = response.artifacts[0];
  const base64Data = artifact.base64;
  const finishReason = artifact.finishReason;
  
  const randomId = Math.floor(Math.random() * 10000000000).toString().padStart(10, '0');
  const filename = `${age}-${gender}-${randomId}-face.jpeg`;
  
  const metadata = {
    id: `img_${Date.now()}_${randomId}`,
    filename,
    age,
    gender,
    ethnicity: 'japanese',
    seed,
    finishReason,
    created_at: new Date().toISOString()
  };
  
  return {
    imageData: base64Data,
    metadata
  };
}

/**
 * Validate age parameter
 * 
 * @param {number|string} age - Age to validate
 * @returns {number} - Validated age
 */
function validateAge(age) {
  const validAges = Object.keys(AGE_DISTRIBUTION).map(Number);
  const parsedAge = Number(age);
  
  if (!validAges.includes(parsedAge)) {
    throw new Error(`Invalid age: ${age}. Must be one of: ${validAges.join(', ')}`);
  }
  
  return parsedAge;
}

/**
 * Validate gender parameter
 * 
 * @param {string} gender - Gender to validate
 * @returns {string} - Validated gender
 */
function validateGender(gender) {
  const validGenders = Object.keys(GENDER_DISTRIBUTION);
  
  if (!validGenders.includes(gender)) {
    throw new Error(`Invalid gender: ${gender}. Must be one of: ${validGenders.join(', ')}`);
  }
  
  return gender;
}

module.exports = {
  generateFaceImage,
  validateAge,
  validateGender,
  AGE_DISTRIBUTION,
  GENDER_DISTRIBUTION
};
