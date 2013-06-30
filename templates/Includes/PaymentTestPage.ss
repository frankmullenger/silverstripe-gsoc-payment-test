<% if ExceptionMessage %>
  <p>
    <strong>Error:</strong> $ExceptionMessage <br />
    <% if ValidationMessage %>
      <strong>Validation Error:</strong> $ValidationMessage
    <% end_if %>
  </p>
  $OrderForm
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
        <td>
        	<% loop Errors %>
        		$ErrorCode 
        	<% end_loop %>
        </td>
        <td>
        	<% loop Errors %>
        		$ErrorMessage 
        	<% end_loop %>
        </td>
        <td>$HTTPStatus</td>
      </tr>
    </tbody>
  </table>
<% end_control %>
