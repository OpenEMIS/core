const PDFDocument = require('pdfkit');
const logger = require('./loggingService');

/**
 * Generates a professional-looking transcript PDF from student data.
 * The generated PDF is written to a writable stream (e.g., an HTTP response).
 *
 * @param {object} data - The student transcript data, including studentInfo, grades, and summary.
 * @param {stream.Writable} stream - The stream to which the PDF content will be written.
 */
const generateTranscriptPdf = (data, stream) => {
  try {
    const doc = new PDFDocument({
      size: 'A4',
      margin: 50,
      info: {
        Title: `Transcript for ${data.studentInfo.studentName}`,
        Author: 'Transcript Service',
      },
    });

    // Pipe the PDF document to the provided stream.
    doc.pipe(stream);

    // --- Document Header ---
    doc.font('Helvetica-Bold').fontSize(18).text('Official Academic Transcript', { align: 'center' });
    doc.font('Helvetica').fontSize(14).text(data.studentInfo.school || 'Ministry of Education', { align: 'center' });
    doc.moveDown(2);

    // --- Student Information Section ---
    doc.font('Helvetica-Bold').fontSize(12).text('Student Information');
    doc.rect(doc.x, doc.y, 500, 1).stroke(); // Underline
    doc.moveDown(0.5);
    doc.font('Helvetica').fontSize(10)
      .text(`Name: ${data.studentInfo.studentName || 'N/A'}`)
      .text(`Student ID: ${data.studentInfo.studentIdentifier || 'N/A'}`);
    doc.moveDown(2);

    // --- Academic Record Section ---
    doc.font('Helvetica-Bold').fontSize(12).text('Academic Record');
    doc.rect(doc.x, doc.y, 500, 1).stroke(); // Underline
    doc.moveDown(0.5);

    // --- Grades Table ---
    const tableTop = doc.y;
    const colWidths = { course: 350, grade: 75, credits: 75 };
    const positions = {
      course: doc.x,
      grade: doc.x + colWidths.course,
      credits: doc.x + colWidths.course + colWidths.grade,
    };

    // Table Header
    doc.font('Helvetica-Bold');
    doc.text('Course Name', positions.course, tableTop);
    doc.text('Grade', positions.grade, tableTop, { width: colWidths.grade, align: 'center' });
    doc.text('Credits', positions.credits, tableTop, { width: colWidths.credits, align: 'center' });
    doc.moveDown(0.5);
    doc.rect(doc.x, doc.y, 500, 0.5).stroke(); // Header underline
    doc.moveDown(0.5);

    // Table Rows
    doc.font('Helvetica');
    data.grades.forEach(grade => {
      const rowY = doc.y;
      doc.text(grade.courseName, positions.course, rowY, { width: colWidths.course - 10 });
      doc.text(grade.grade, positions.grade, rowY, { width: colWidths.grade, align: 'center' });
      doc.text(grade.credits.toString(), positions.credits, rowY, { width: colWidths.credits, align: 'center' });
      doc.moveDown(0.5);
    });
    doc.moveDown(1);

    // --- Summary Section ---
    doc.font('Helvetica-Bold').fontSize(11)
      .text(`Total Credits Earned: ${data.summary.totalCredits}`, { align: 'right' })
      .text(`Cumulative GPA: ${data.summary.gpa}`, { align: 'right' });
    doc.moveDown(4);

    // --- Footer ---
    const generationDate = new Date().toUTCString();
    doc.font('Helvetica-Oblique').fontSize(8)
      .text('*** End of Transcript ***', { align: 'center' })
      .text(`Generated on: ${generationDate}`, { align: 'center' });

    // Finalize the PDF. This is important to end the stream correctly.
    doc.end();

    logger.info(`Successfully initiated PDF generation for student ID: ${data.studentInfo.id}`);

  } catch (error) {
    logger.error(`Failed to generate PDF for student ID ${data.studentInfo.id}:`, error);
    // If an error occurs and the stream is still open, end it to prevent hanging.
    if (!stream.writableEnded) {
      stream.end('An error occurred while generating the PDF.');
    }
  }
};

module.exports = {
  generateTranscriptPdf,
};
