function add_new_contrib() {
  jQuery(".wpgd-new-contrib").show();
}

jQuery(function() {
  var $ = jQuery;

  function reduce(arr,fn) {
    var ret = $([]);
    for(var i = 0; i < arr.length; i++) {
      var x = $(arr[i]);
      if (fn(x))  {
        ret = ret.add(x);
      }
    }
    return ret;
  }

  //form to insert contribution
  $(".wpgd-new-contrib input[name=Cancel]").click(
    function() { $(".wpgd-new-contrib").hide()});

  $(".wpgd-new-contrib input[name=OK]").click(function() {
    var theme = $(".wpgd-new-contrib select").val();
    var title = $(".wpgd-new-contrib input[name=title]").attr("value");
    var content = $(".wpgd-new-contrib textarea").val();
    var part = $(".wpgd-new-contrib input[name=part]").attr("value");
    slow_operation(function(done) {
      $.ajax({
        url: 'admin-ajax.php',
        type: 'post',
        data: {action:'insert_contrib',
               data:{theme:theme,title:title,content:content, part:part}},
        success: function(data) {
          done();
          window.location.reload();
        }
      });
    });
  });

  //delete contrib
  reduce($(".delete-contrib"), function(x) {
    return is_approved(x.attr("href"));
  }).hide();

  reduce($(".delete-contrib"), function(x) {
    return !is_approved(x.attr("href"));
  }).click(function(ev) {
    ev.preventDefault();
    var id = $(this).attr("href");
    if (confirm("Are you sure you want to delete?")) {
      if ($(".child-of-"+id).length > 0) {
        alert("Unassociate children before removing this");
        return;
      }
      slow_operation(function(done) {
        $.ajax({
          url: 'admin-ajax.php',
          type: 'post',
          data: {action:'delete_contrib',
                 data:{id:id}},
          success: function(data) {
            done();
            window.location.reload();
          }
        });
      });
    }
  });

  //careful: the form has a .wpgd-theme too!
  reduce($(".wp-list-table .wpgd-theme"), function(x) {
    var id = /\[([0-9]+)\]/.exec(x.attr("id"))[1];
    return is_approved(id);
  }).each(function() {
    $(this).replaceWith("<span>"+$(this).val()+"</span>")
  });

  $(".wp-list-table .wpgd-theme").change(function() {
    var self = $(this);
    var id = /\[([0-9]+)\]/.exec(self.attr("id"))[1];
    var current = /wpgd-the-theme\[([a-zA-Z]+)\]/.exec(self.attr("class"))[1];
    if(confirm("Change the theme?")) {
      var data = {id:id,field:'theme', 'theme': self.val()};
      slow_operation(function(done) {
        $.ajax({
          url: 'admin-ajax.php',
          type: 'post',
          data: {action:'update_contrib',
                 data:data},
          success: function(data) {
            done();
            window.location.reload();
          }
        });
      });
    } else {
      self.val(current);
    }
  });

  //"loading..." stuff
  function slow_operation(fn) {
    $(".wpgd-status-bar").slideDown();
    fn(function() { $(".wpgd-status-bar").slideUp(); });
  }

  //useful functions...
  function get_row_id(tr) {
    return parseInt(tr.attr("id").split("-")[1]);
  }

  function is_child(id) {
    return $("#row-"+id).hasClass("is-child");
  }

  function is_parent(id) {
    return !is_child(id);
  }

  function is_approved(id) {
    return $("#row-"+id).hasClass('wpgd-approved');
  }

  function move_parent_row(id) {
    var trs = $("#contrib-rows tr");
    var tr = null;
    for (var i = 0; i < trs.length; i++) {
      var trid = get_row_id($(trs[i]));
      if (trid == id) continue;
      if (trid > id) {
        tr = $(trs[i]);
        break;
      }
    }
    if (tr) {
      //there is a tr bigger than id, insert ourself before it
      tr.before($("#row-"+id).detach());
    } else {
      //we are the bigger id. insert after the last
      //note: the last could also be ourself
      var last_tr = $(trs[trs.length-1]);
      if (get_row_id(last_tr) != id) {
        $(trs[trs.length-1]).after($("#row-"+id).detach());
      } //else, stay were we are, already the last
    }
  }

  //event binder
  function inliner(dbfield, accessor, editable) {
    var original_text;
    var td;
    var p;
    function show_field() {
      p = $(this).find('p');
      td = p.parent();
      p.unbind("dblclick", arguments.callee);
      var id = /\[([0-9]+)\]/.exec(td.attr("id"))[1];
      original_text = p.html();

      var ok = $("<input type='submit' value='OK'>").click(function() {
        var data = {id:id,field:dbfield};
        data[dbfield] = accessor.call(editable);

        slow_operation(function(done) {
          $.ajax({
            url: 'admin-ajax.php',
            type: 'post',
            data: {action:'update_contrib',data:data},
            success: function(data) {
              done();
              original_text = accessor.call(editable);
              revert_editable();
            }
          });
        });
      });

      var cancel = $("<input type='submit' value='Cancel'>")
        .click(revert_editable);

      accessor.call(editable, p.html());
      p.html('');
      p.append(editable).append(ok).append(cancel);
    }
    function revert_editable() {
      p.bind('dblclick',show_field);
      p.html(original_text);
    }
    return show_field;
  }

  //binding events: title and content inline editing

  $(".contribution").bind(
    'dblclick',inliner('content',$().val, $("<textarea/>")));

  $(".contribution-title").bind(
    'dblclick',inliner(
      'title',function(val) {
        if(val)
          return this.attr('value',val);
        else
          return this.attr('value');
      },
      $("<input type='text'/>")));


  //hiding checkboxes of approved contribs
  reduce($(".contrib-status input"), function(x) {
    var td = x.parent();
    var tr = td.parent();
    var id = /\[([0-9]+)\]/.exec(td.attr("id"))[1];
    return is_approved(id)
  }).hide();

  //hiding duplicate field of approved contribs
  reduce($(".contrib-duplicates"), function(x) {
    var id = /\[([0-9]+)\]/.exec(x.attr("id"))[1];
    return is_approved(id);
  }).each(function() {
    $(this).replaceWith("<span></span>")
  });


  $(".contrib-status input").change(function() {
    var td = $(this).parent();
    var tr = td.parent();
    var id = /\[([0-9]+)\]/.exec(td.attr("id"))[1];
    var data = {id:id,field:'status'};
    if (!confirm("Are you sure? There is no way to" +
                 " disapprove the contribution latter")) {
      $(this).attr("checked",false);
      return;
    }
    $(this).hide();
    slow_operation(function(done) {
      $.ajax({
        url: 'admin-ajax.php',
        type: 'post',
        data: {action:'update_contrib',data:data},
        success: function(data) {
          if (data == 'error') alert("There was an error approving "+ id);
          done();
          window.location.reload();
        }
      });
    });
  });

  $(".contrib-parts").change(function() {
    var self = $(this);
    var id = /\[([0-9]+)\]/.exec(self.attr("id"))[1];
    var parent = /contrib-part\[([0-9]+)\]/.exec(self.attr("class"))[1];
    var new_parent = self.val();
    if (is_child(new_parent)) {
      alert("Can't be a part of a part");
      self.val(parent);
      return;
    }

    // if (new_parent != 0 && $("#row-"+new_parent).length == 0) {
    //   alert("Can't find contrib with ID = " + new_parent);
    //   self.val(parent);
    //   return;
    // }

    if (id == new_parent) {
      alert("Can't be part of itself");
      self.val(parent);
      return;
    }

    if (parent == new_parent) return;

    if (!confirm("Confirm change?")) {
      self.val(parent);
      return;
    }

    var data = {id:id,field:'part', part:new_parent};
    slow_operation(function(done) {
      $.ajax({
        url: 'admin-ajax.php',
        type: 'post',
        data: {action:'update_contrib',data:data},
        success: function(res) {
          done();
          if (res == 'not-found') {
            alert("Contribution " + id + " not found");
            self.val(parent);
          } else {
            window.location.reload();
          }
        }
      });
    });
  });

  $(".contrib-duplicates").change(function() {
    var self = $(this);
    var id = /\[([0-9]+)\]/.exec(self.attr("id"))[1];
    var parent = /contrib-duplicate\[([0-9]+)\]/.exec(self.attr("class"))[1];
    var new_parent = self.val();

    if (is_child(new_parent)) {
      alert("Can't be a duplicated of a duplicated")
      self.val(parent);
      return;
    }

    // if (new_parent != 0 && $("#row-"+new_parent).length == 0) {
    //   alert("Can't find contrib with ID = " + new_parent);
    //   self.val(parent);
    //   return;
    // }

    if (id == new_parent) {
      alert("Can't be duplicated of itself");
      self.val(parent);
      return;
    }

    if (parent == self.val()) return;

    if (!confirm("Confirm change?")) {
      self.val(parent);
      return;
    }

    var data = {id:id,field:'parent', parent:new_parent};
    slow_operation(function(done) {
      $.ajax({
        url: 'admin-ajax.php',
        type: 'post',
        data: {action:'update_contrib',data:data},
        success: function(res) {
          done();
          self.val(parent);
          if (res == 'not-found') {
            alert("Contribution " + id + " not found");
          } else {
            window.location.reload();
          }
        }
      });
    });
  });

  $(".wp-list-table").show();
  $(".wp-list-table-loading").hide();
});
