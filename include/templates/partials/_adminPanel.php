    <div id="admin" class="modal">
      <div class="modal-header">
        <h3>Edition</h3>
      </div>
      <div class="form-inline modal-body">
        <div class="form-line">
          <label for="addEdge_type"><strong>Type</strong> : </label> <select id="addEdge_type"></select>
        </div>
        <div class="form-line">
          <strong>Automatically make both ways :</strong><br />
          <input name="addEdge_autoReverse" type="radio" value="0"><label class="radio" for="addEdge_autoReverse">None (q)</label>
          <input name="addEdge_autoReverse" type="radio" value="same"><label class="radio text-success" for="addEdge_autoReverse">Same (s)</label>
          <input name="addEdge_autoReverse" type="radio" value="3"><label class="radio text-warning" for="addEdge_autoReverse">Cycles (d)</label>
          <input name="addEdge_autoReverse" type="radio" value="4" checked><label class="radio text-error" for="addEdge_autoReverse">Walk (f)</label>
        </div>  
        <div class="form-line">
          <label for="addEdge_continuous"><strong>Continous mode (z): </strong> </label> <input name="addEdge_continuous" id="addEdge_continuous" type="checkbox" value="0">
        </div>
        <p class="muted"><b class="icon-info-sign"></b> <em>Mouse over an edge to make it editable.<br/>To delete an edge, right-click on it.<br/>Vertices will auto-merge under <?php echo _closestPointRadius_edit; ?>m.</em></p>
        <div class="form-line">
          <p><b class="icon-list-alt"></b> <strong>Keyboard shortcuts :</strong></p>
          <ul>
            <li><em>in (parenthesis) for each action</em></li>
            <li>space : <em>Toggle overlays</em></li>
            <li>esc : <em>Stop add edge & drop pin</em></li>
          </ul>
        </div>
      </div>
      <div class="modal-footer">
        <button id="addEdge" class="btn btn-info" ><b class="icon-plus-sign icon-white"></b> Add edges (a)</button>
      </div>
    </div>