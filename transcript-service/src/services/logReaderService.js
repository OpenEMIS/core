const fs = require('fs');
const path = require('path');
const readline = require('readline');
const config = require('../../config/config');
const { logger } = require('./loggingService');

/**
 * A service dedicated to reading and parsing application logs.
 * This is used to power the /logs API endpoint.
 */

/**
 * Reads all log files, parses them, and returns structured log entries.
 * It specifically looks for log files created by the DailyRotateFile transport.
 *
 * @returns {Promise<Array<object>>} A promise that resolves to an array of log event objects.
 */
const getTranscriptLogs = async () => {
  const logDir = config.outputDir;
  let logFiles;

  try {
    // Read all files from the configured output directory.
    const allFiles = await fs.promises.readdir(logDir);
    // Filter for the transcript event log files and sort them chronologically.
    logFiles = allFiles
      .filter(f => f.startsWith('transcript-events-') && f.endsWith('.log'))
      .sort();
  } catch (err) {
    // If the directory doesn't exist, it means no logs have been created yet.
    if (err.code === 'ENOENT') {
      logger.warn('Log directory does not exist. Returning empty log array.');
      return [];
    }
    // For other errors, re-throw the exception.
    logger.error('Failed to read log directory:', err);
    throw new Error('Could not read log directory.');
  }

  if (logFiles.length === 0) {
    logger.info('No transcript log files found in the output directory.');
    return [];
  }

  const allLogs = [];

  // Process each log file.
  for (const logFile of logFiles) {
    const filePath = path.join(logDir, logFile);
    const fileStream = fs.createReadStream(filePath);
    const rl = readline.createInterface({
      input: fileStream,
      crlfDelay: Infinity, // Handles different line endings
    });

    // Read file line by line.
    for await (const line of rl) {
      if (line) {
        try {
          // Since logs are in JSON format, parse each line.
          const logEntry = JSON.parse(line);
          allLogs.push(logEntry);
        } catch (e) {
          logger.error(`Failed to parse a line in log file ${logFile}:`, { line, error: e.toString() });
        }
      }
    }
  }

  // Return logs in reverse chronological order (newest first).
  return allLogs.reverse();
};

module.exports = {
  getTranscriptLogs,
};
