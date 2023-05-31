var projectFiles = {
    el: "projectFiles",
    itemType: "file",
    url: "managefile.php?action=projectFiles",
    dependencies: []
};


/*
 * Function to load a new folder to the files view
 */
function loadFolder(view, folder) {
    var currentUrl = view.$get("url");

    //modify the data url
    Vue.set(view, "url", currentUrl + "&folder=" + folder);
    //trigger update
    updateView(view);
}
/*
 * Select a folder in the add file, add folder dropdowns
 */
function selectFolder(folderId) {
    var theParentOptions = cssId("folderparent").options;
    for (i = 0; i < theParentOptions.length; i++) {
        if (theParentOptions[i].value == folderId) {
            theParentOptions[i].selected = 'selected';
        }
    }
    var theOptions = cssId('upfolder').options;
    for (i = 0; i < theOptions.length; i++) {
        if (theOptions[i].value == folderId) {
            theOptions[i].selected = 'selected';
        }
    }
}

function changeFileview(fileviewType, project) {
    //grid view
    if (fileviewType == "grid") {
        window.location = "managefile.php?action=showproject&id=" + project;
    }
    else if (fileviewType == "list") {
        window.location = "managefile.php?action=showproject&viewmode=list&id=" + project;
    }
}
/*
 * DRAG AND DROP
 */
/*
 * Function called when the user drops the element on a droppable
 *
 */
function handleDrop(evt) {
    //prevent default event handling
    evt.stopPropagation();
    evt.preventDefault();

    //get the droptarget element
    var elm = evt.target;
    //get its id
    var elmId = elm.getAttribute("data-folderid");
    //get the ID of the droped file
    var dropedElmId = evt.dataTransfer.getData("text/plain");
    //get the droped element
    var dropedElm = document.getElementById("fli_" + dropedElmId);
    //hide it
    dropedElm.style.display = "none";

    //persist the move of the file on the server
    change('managefile.php?action=movefile&id=100&file=' + dropedElmId + '&target=' + elmId, 'jslog');

    console.log("droped");
    console.log("fileid " + dropedElmId + "folder " + elmId);

}

function handleDragStart(evt) {
    console.log("drag start");
    //set the drag type to move
    evt.dataTransfer.effectAllowed = 'move';

    //get the dragged element
    var elm = evt.target;
    //get the id of the dragged file
    var elmId = elm.getAttribute("data-fileid");

    //set the file id to the data transfer object
    evt.dataTransfer.setData("text/plain", elmId);

    //make the dragged element
    elm.style.opacity = '0.4';  // this / e.target is the source node.

}

function handleDragOver(evt) {
    if (evt.preventDefault) {
        evt.preventDefault(); // Necessary. Allows us to drop.
    }
    evt.dataTransfer.dropEffect = 'move';  // See the section on the DataTransfer object.

    return false;
}

function handleDragEnter(evt) {
    // this / e.target is the current hover target.
    var elm = evt.target;
    var data = evt.dataTransfer.getData("text/plain");

    var wrapperElm = document.getElementById("iw_" + data);
    elm.classList.add('dragover');
}

function handleDragLeave(evt) {
    var elm = evt.target;

    elm.classList.remove('dragover');  // this / e.target is previous target element.
}
var basicImgPath = "templates/standard/theme/standard/images/symbols/";

function renderFolders(tree, folders) {
    if (folders.length > 0) {
        console.log(folders);
        for (var f = 0; f < folders.length; f++) {
            var folder = folders[f];
            if(folder.subfolders !== false){
                renderFolders(tree, folder.subfolders);
            }
            tree.add("fo" + folder.ID, "fo" + folder.parent, folder.name, "javascript:alert('bla')", "", "", basicImgPath + "folder.png", basicImgPath + "folder.png", "", 0);
        }
    }
}
function renderFiles(tree, files, folder){
    if (files.length > 0) {
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            //ID of the current item to draw a tree for
            console.log(file.folder);

            tree.add("fi" + file.ID, "fo" + file.folder, file.name, "managefile.php?action=downloadfile&amp;id=" + file.project + "&amp;file=" + file.ID, "", "", basicImgPath + "files.png", basicImgPath + "files.png", "", 0);
        }

    }
}
function renderFilesTree(view) {
    var treeName = "filesTree";

    var treeItems = view.items;

    if (treeItems != undefined) {
        //initialise tree component
        var messageTree = new dTree(treeName);
        messageTree.add(0, -1, '');


        var files = treeItems.files;
        var folders = treeItems.folders;
        messageTree.add("fo0", 0, "Root folder", "managefile.php?action=downloadfile&amp;id=" + "&amp;file=", "", "", basicImgPath + "folder.png", basicImgPath + "folder.png", "", 0);
        renderFolders(messageTree, folders);
        renderFiles(messageTree, files, 0);
    }

    //write the tree to the target element

    cssId(treeName).innerHTML = messageTree;
    //export global variable so the tree is clickable
    window[treeName] = messageTree;
    window[treeName].openAll();
}