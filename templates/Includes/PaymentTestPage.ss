<% if ExceptionMessage %>
  <p>$ExceptionMessage</p>
<% end_if %>

<% if ValidationMessage %>
  <p>$ValidationMessage</p>
<% end_if %>

<% control Payment %>
  <h2>Payment #{$ID} Details</h2>

  <table border="0">
    <thead>
      <tr>
        <th>Method</th>
        <th>Status</th>
        <th>Amount</th>
        <th>Gateway Error Codes</th>
        <th>Gateway Message</th>
        <th>Gateway HTTP Response Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>$Method</td>
        <td>$Status</td>
        <td>$Amount.Nice</td>
        <td>$ErrorCodes</td>
        <td>$Message</td>
        <td>$HTTPStatus</td>
      </tr>
    </tbody>
  </table>
<% end_control %>
