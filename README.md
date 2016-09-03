# XenForo-ReportImprovements

A Collection of improvements to XF's reporting system.

Note; when reports are sent to a forum, the warning<->report links can not be created!

- User tagging in report comments.
- Adds Spam Cleaner links in AdminCP (under actions) and to the Moderation Queue front-end.
- Sends an Alert to moderators who have commented/reported on a report.
 - Only sends an alert if the previous alert hasn't been viewed.
 - Report Alerts link to the actual comments for longer reports.
 - Report Alerts include the title of the report.
- Links Warnings to reports.
 - Visible from the warning itself, and when issuing warnings against content.
- Link Reports to Warnings.
-- Logs changes to Warnings (add/edit/delete), and associates them with a report.
- Automatically create a report for a warning.
- When issuing a Warning, option to resolve any linked report.
- Separate explicit viewing report permissions by content types; conversation, posts, profile posts, user.
- Only view Alerts/Reports for forums the moderator can see (configurable), in addition to viewing report permission.
- Optional ability to log warnings into reports when they expire. This does not disrupt who the report was assigned to, and does not re-open the report.
- Report Comment Likes.
- Report Alerts are logged into Report Comments.
- Search report comments
 - Optional ability to search report comments by associated warning points. (Requires Enhanced Search Improvements add-on)
