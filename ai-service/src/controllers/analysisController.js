const analysisService = require('../services/analysisService');

/**
 * Handles the request to get the list of at-risk students.
 */
const getAtRiskStudents = async (req, res, next) => {
    try {
        const atRiskStudents = await analysisService.identifyAtRiskStudents();
        res.status(200).json({
            message: `Found ${atRiskStudents.length} at-risk student(s).`,
            data: atRiskStudents,
        });
    } catch (error) {
        // Forward any errors to the global error handler
        next(error);
    }
};

module.exports = {
    getAtRiskStudents,
};
