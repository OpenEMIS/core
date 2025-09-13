const PDFDocument = require('pdfkit');
const logger = require('./loggingService');

/**
 * Creates a transcript PDF from student data.
 * @param {object} data - The student and course data.
 * @returns {Promise<Buffer>} A promise that resolves with the PDF buffer.
 */
const createTranscriptPdf = (data) => {
    return new Promise((resolve, reject) => {
        try {
            const doc = new PDFDocument({ margin: 50 });
            const buffers = [];

            doc.on('data', buffers.push.bind(buffers));
            doc.on('end', () => {
                const pdfBuffer = Buffer.concat(buffers);
                resolve(pdfBuffer);
            });
            doc.on('error', (err) => {
                logger.error(`An error occurred in PDF generation: ${err.message}`);
                reject(err);
            });

            // --- PDF Content Generation ---

            // Header
            doc.fontSize(20).text('Official Student Transcript', { align: 'center' });
            doc.moveDown();

            // Institution Info
            doc.fontSize(16).text(data.institution_name || 'Ministry of Education', { align: 'center' });
            doc.moveDown(2);

            // Student Information
            doc.fontSize(14).text('Student Information', { underline: true });
            doc.moveDown();
            doc.fontSize(12).text(`Name: ${data.first_name} ${data.last_name}`);
            doc.text(`Student ID: ${data.student_id}`);
            doc.text(`Date of Birth: ${new Date(data.date_of_birth).toLocaleDateString()}`);
            doc.moveDown(2);

            // Placeholder for Course Grades
            // In a real implementation, this data would come from the 'data' object.
            doc.fontSize(14).text('Academic Record', { underline: true });
            doc.moveDown();

            // Table Header
            const tableTop = doc.y;
            doc.fontSize(12);
            doc.text('Course', 50, tableTop);
            doc.text('Grade', 250, tableTop);
            doc.text('Year', 450, tableTop, {width: 100, align: 'right'});
            doc.moveTo(50, doc.y).lineTo(550, doc.y).stroke();
            doc.moveDown();

            // Table Rows from dynamic data
            const courses = data.courses || [];
            if (courses.length === 0) {
                doc.moveDown();
                doc.fontSize(10).text('No academic record available for this student.', { align: 'center' });
            } else {
                courses.forEach(item => {
                    const y = doc.y;
                    doc.text(item.course_name, 50, y, { width: 190 });
                    doc.text(item.grade, 250, y, { width: 190 });
                    doc.text(item.academic_year, 450, y, { width: 100, align: 'right' });
                    doc.moveDown();
                });
            }

            doc.end();

        } catch (error) {
            logger.error(`Failed to create PDF: ${error.message}`);
            reject(error);
        }
    });
};

module.exports = {
    createTranscriptPdf,
};
