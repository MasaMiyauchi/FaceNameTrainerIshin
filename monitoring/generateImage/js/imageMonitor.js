/**
 * Image Generation Monitoring Module
 * 
 * Provides functions for monitoring the image generation process,
 * collecting metrics, and generating reports.
 */

/**
 * Monitor image generation process
 * 
 * @param {Function} generationFunction - The image generation function to monitor
 * @param {Object} params - Parameters to pass to the generation function
 * @returns {Promise<Object>} - Monitoring results and generated image data
 */
async function monitorImageGeneration(generationFunction, params) {
  const startTime = Date.now();
  let result = null;
  let error = null;
  
  try {
    result = await generationFunction(params);
    
    const endTime = Date.now();
    const responseTime = endTime - startTime;
    
    const monitoringData = {
      id: `mon_${Date.now()}_${Math.floor(Math.random() * 1000000)}`,
      timestamp: new Date().toISOString(),
      requestParams: { ...params },
      performance: {
        responseTime,
        success: true
      },
      quality: calculateQualityMetrics(result),
      errors: null
    };
    
    await saveMonitoringData(monitoringData);
    
    return {
      ...result,
      monitoring: monitoringData
    };
  } catch (err) {
    error = err;
    
    const endTime = Date.now();
    const responseTime = endTime - startTime;
    
    const monitoringData = {
      id: `mon_${Date.now()}_${Math.floor(Math.random() * 1000000)}`,
      timestamp: new Date().toISOString(),
      requestParams: { ...params },
      performance: {
        responseTime,
        success: false
      },
      quality: null,
      errors: {
        message: err.message,
        stack: err.stack
      }
    };
    
    await saveMonitoringData(monitoringData);
    
    throw err;
  }
}

/**
 * Calculate quality metrics for generated image
 * 
 * @param {Object} result - The result from image generation
 * @returns {Object} - Quality metrics
 */
function calculateQualityMetrics(result) {
  return {
    qualityScore: 0.85 + (Math.random() * 0.15), // Simple random score for demo
    promptCompliance: 0.9 + (Math.random() * 0.1)
  };
}

/**
 * Save monitoring data to storage
 * 
 * @param {Object} monitoringData - Monitoring data to save
 * @returns {Promise<void>}
 */
async function saveMonitoringData(monitoringData) {
  
  try {
    const existingData = localStorage.getItem('imageGenerationMonitoring');
    const monitoringArray = existingData ? JSON.parse(existingData) : [];
    
    monitoringArray.push(monitoringData);
    
    localStorage.setItem('imageGenerationMonitoring', JSON.stringify(monitoringArray));
    
    console.log(`Saved monitoring data: ${monitoringData.id}`);
  } catch (error) {
    console.error('Error saving monitoring data:', error);
  }
}

/**
 * Generate a batch of images with monitoring
 * 
 * @param {number} count - Number of images to generate
 * @param {Object} options - Generation options
 * @returns {Promise<Object>} - Batch results and statistics
 */
async function monitorBatchGeneration(count, options) {
  const results = [];
  const errors = [];
  const startTime = Date.now();
  
  for (let i = 0; i < count; i++) {
    try {
      const params = generateRandomParams(options);
      
      const result = await monitorImageGeneration(generateFaceImage, params);
      results.push(result);
    } catch (error) {
      errors.push(error);
      console.error(`Error in batch generation (${i+1}/${count}):`, error);
    }
  }
  
  const endTime = Date.now();
  const totalTime = endTime - startTime;
  const averageResponseTime = results.length > 0 
    ? results.reduce((sum, r) => sum + r.monitoring.performance.responseTime, 0) / results.length 
    : 0;
  const errorRate = errors.length / count;
  
  return {
    results,
    errors,
    stats: {
      totalTime,
      averageResponseTime,
      errorRate,
      successCount: results.length,
      errorCount: errors.length
    }
  };
}

/**
 * Generate random parameters based on options
 * 
 * @param {Object} options - Options for parameter generation
 * @returns {Object} - Random parameters for image generation
 */
function generateRandomParams(options) {
  const { AGE_DISTRIBUTION, GENDER_DISTRIBUTION } = require('./imageGenerator');
  
  const age = options.age || selectRandomByDistribution(AGE_DISTRIBUTION);
  
  const gender = options.gender || selectRandomByDistribution(GENDER_DISTRIBUTION);
  
  return { age, gender };
}

/**
 * Select a random value based on a distribution
 * 
 * @param {Object} distribution - Distribution object with values as probabilities
 * @returns {string|number} - Randomly selected key based on distribution
 */
function selectRandomByDistribution(distribution) {
  const rand = Math.random();
  let sum = 0;
  
  for (const [value, probability] of Object.entries(distribution)) {
    sum += probability;
    if (rand < sum) {
      return value;
    }
  }
  
  return Object.keys(distribution)[0]; // Fallback to first value
}

const { generateFaceImage } = require('./imageGenerator');

module.exports = {
  monitorImageGeneration,
  monitorBatchGeneration,
  calculateQualityMetrics,
  saveMonitoringData,
  generateRandomParams
};
