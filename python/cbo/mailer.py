
import configurator
import smtplib, email
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

def send(to, subject, body):
	try:
		smtpserver = smtplib.SMTP("smtp.gmail.com", 587)
		smtpserver.ehlo()
		smtpserver.starttls()
		smtpserver.login(configurator.getGmailAuth('email') , configurator.getGmailAuth('password'))
		emailMessageHtml = body
		msg = MIMEMultipart('alternative')
		msg['Subject'] = subject
		msg['From'] = "Coinbase Orders <%s>" % configurator.getGmailAuth('email')
		msg['To'] = "%s" % to
		# Record the MIME types of both parts - text/plain and text/html.
		part1 = MIMEText(emailMessageHtml, 'plain')
		part2 = MIMEText(emailMessageHtml, 'html')
		msg.attach(part1)
		msg.attach(part2)
		smtpserver.sendmail(msg['From'], msg['To'], msg.as_string())
		smtpserver.close()
	except:
		raise
