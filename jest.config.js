module.exports = {
  testEnvironment: 'jsdom',
  testMatch: [
    '**/client/src/**/__tests__/**/*.js',
    '**/client/src/**/*.test.js'
  ],
  collectCoverageFrom: [
    'client/src/js/**/*.js',
    '!client/src/js/**/*.test.js'
  ],
  coverageDirectory: 'coverage',
  setupFilesAfterEnv: ['<rootDir>/jest.setup.js']
};
