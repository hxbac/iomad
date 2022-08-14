function hasClass(element, className)
{
    return element.classList.contains(className);
}

function treeView(element)
{
    element.addEventListener("click", (e) => {
        let target = e.target;
       
        console.log(target);
        
        if(hasClass(target, "branch-node"))
            target.classList.toggle("open");
        else if(hasClass(target, "branch-title"))
            target.parentNode.classList.toggle("open");
    });
}

var myTreeView = document.getElementById("my-tree-view");

treeView(myTreeView);