/**
 * Image Generation and Monitoring Module
 * 
 * Main entry point for the image generation and monitoring functionality.
 */

const { 
  generateFaceImage,
  validateAge,
  validateGender
} = require('./imageGenerator');

const {
  monitorImageGeneration,
  monitorBatchGeneration
} = require('./imageMonitor');

module.exports = {
  generateFaceImage,
  monitorImageGeneration,
  monitorBatchGeneration
};
