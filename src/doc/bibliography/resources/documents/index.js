var fs = require('fs')
var path = require('path')

var docs = {
  pages: [
    'about',
    'privacy',
    'tos',
    'wiki'
  ],
  asides: [
    'about',
    'legal',
    'wiki',
    'donate'
  ],
  alerts: [
    'petition'
  ],
  emails: [
    'reset',
    'stripe-receipt'
  ]
}

exports = module.exports = {
  pages: {},
  asides: {},
  alerts: {},
  emails: {}
}

docs.pages.forEach(function (page) {
  exports.pages[page] = '<article>' + readHTML('pages', page) + '</article>'
})

docs.asides.forEach(function (aside) {
  exports.asides[aside] = readHTML('asides', aside)
})

docs.alerts.forEach(function (alert) {
  exports.alerts[alert] = readHTML('alerts', alert)
})

docs.emails.forEach(function (email) {
  exports.emails[email] = readText('emails', email)
})

function readHTML(folder, filename) {
  return fs.readFileSync(path.join(__dirname, folder, filename + '.html'), 'utf8').replace(/\n\s*/g, '')
}

function readText(folder, filename) {
  return fs.readFileSync(path.join(__dirname, folder, filename + '.txt'), 'utf8')
}