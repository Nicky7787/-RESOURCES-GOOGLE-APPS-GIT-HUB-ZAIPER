Use this endpoint to invite a new member to your team. An email with a link to join the team will be sent to the new member email address. If member doesn't exists in the project it will be automatically created.

Use the redirect parameter to redirect the user from the invitation email back to your app. When the user is redirected, use the /teams/{teamId}/memberships/{inviteId}/status endpoint to finally join the user to the team.

Please notice that in order to avoid a [Redirect Attacks](https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.md) the only valid redirect URL's are the once from domains you have set when added your platforms in the console interface.